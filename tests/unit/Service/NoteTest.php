<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit\Service;

use OCA\Notes\Service\Note;
use OCA\Notes\Service\NoteUtil;
use OCA\Notes\Service\SettingsService;
use OCA\Notes\Service\TagService;
use OCA\Notes\Service\Util;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Share\IManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A note has no state of its own — title, category and excerpt are all derived
 * from the file's name, path and content. That derivation is what these tests
 * cover; the write side needs a real storage and lives in tests/api/.
 */
class NoteTest extends TestCase {
	private const NOTES_PATH = '/alice/files/Notes';

	private NoteUtil $noteUtil;

	protected function setUp(): void {
		parent::setUp();

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$db = $this->createMock(IDBConnection::class);
		$db->method('supports4ByteText')->willReturn(true);

		$this->noteUtil = new NoteUtil(
			new Util($l10n, $this->createMock(LoggerInterface::class)),
			$this->createMock(IRootFolder::class),
			$db,
			$this->createMock(TagService::class),
			$this->createMock(IManager::class),
			$this->createMock(IUserSession::class),
			$this->createMock(SettingsService::class),
		);
	}

	/**
	 * @param string $path full path of the note file
	 * @param string|false $content what the storage returns for getContent()
	 */
	private function note(string $path, string|false $content = '', int $size = 1): Note {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn(basename($path));
		$file->method('getPath')->willReturn($path);
		$file->method('getContent')->willReturn($content);
		$file->method('getSize')->willReturn($size);

		$notesFolder = $this->createMock(Folder::class);
		$notesFolder->method('getPath')->willReturn(self::NOTES_PATH);

		return new Note($file, $notesFolder, $this->noteUtil);
	}

	// ---- title -------------------------------------------------------------

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function fileNames(): array {
		return [
			'txt' => ['Shopping.txt', 'Shopping'],
			'md' => ['Shopping.md', 'Shopping'],
			'dots in name' => ['v1.2.notes.md', 'v1.2.notes'],
			'no extension' => ['Makefile', 'Makefile'],
			'spaces' => ['My shopping list.txt', 'My shopping list'],
			'unicode' => ['Grüße 日本語.md', 'Grüße 日本語'],
			'numbered collision' => ['Shopping (2).txt', 'Shopping (2)'],
		];
	}

	#[DataProvider('fileNames')]
	public function testTitleComesFromTheFileName(string $fileName, string $expected): void {
		self::assertSame($expected, $this->note(self::NOTES_PATH . '/' . $fileName)->getTitle());
	}

	// ---- category ----------------------------------------------------------

	public function testNoteDirectlyInTheNotesFolderHasNoCategory(): void {
		self::assertSame('', $this->note(self::NOTES_PATH . '/loose.txt')->getCategory());
	}

	public function testCategoryIsTheFolderBelowTheNotesFolder(): void {
		self::assertSame('Work', $this->note(self::NOTES_PATH . '/Work/a.txt')->getCategory());
	}

	public function testNestedCategoryKeepsItsFullPath(): void {
		self::assertSame(
			'Work/Projects/2026',
			$this->note(self::NOTES_PATH . '/Work/Projects/2026/a.txt')->getCategory(),
		);
	}

	public function testCategoryHandlesUnicodeFolderNames(): void {
		self::assertSame('Grüße', $this->note(self::NOTES_PATH . '/Grüße/a.txt')->getCategory());
	}

	// ---- content -----------------------------------------------------------

	public function testUtf8ByteOrderMarkIsStripped(): void {
		$note = $this->note(self::NOTES_PATH . '/a.md', "\u{FEFF}# Heading");

		self::assertSame('# Heading', $note->getContent(), 'a BOM would show up as a stray glyph');
	}

	public function testEmptyFileOnObjectStorageReadsAsEmptyString(): void {
		// object storage returns false rather than '' for a zero-byte file
		$note = $this->note(self::NOTES_PATH . '/a.md', false, 0);

		self::assertSame('', $note->getContent());
	}

	public function testUnreadableContentThrows(): void {
		$note = $this->note(self::NOTES_PATH . '/a.md', false, 42);

		$this->expectException(\Exception::class);
		$note->getContent();
	}

	// ---- excerpt -----------------------------------------------------------

	public function testExcerptSkipsTheTitleLine(): void {
		$note = $this->note(self::NOTES_PATH . '/Shopping.txt', "Shopping\nmilk and eggs");

		self::assertSame('milk and eggs', $note->getExcerpt());
	}

	public function testExcerptStripsMarkdownAndFlattensNewlines(): void {
		$note = $this->note(self::NOTES_PATH . '/Shopping.txt', "Shopping\n- milk\n- eggs");

		// newlines become em-spaces so the excerpt stays on one line
		self::assertSame("milk\u{2003}eggs", $note->getExcerpt());
	}

	public function testExcerptKeepsContentThatDoesNotRepeatTheTitle(): void {
		$note = $this->note(self::NOTES_PATH . '/Shopping.txt', 'milk and eggs');

		self::assertSame('milk and eggs', $note->getExcerpt());
	}

	public function testExcerptIsTruncatedWithAnEllipsis(): void {
		$note = $this->note(self::NOTES_PATH . '/a.txt', str_repeat('x', 250));

		$excerpt = $note->getExcerpt();

		self::assertSame(101, mb_strlen($excerpt, 'utf-8'), '100 characters plus the ellipsis');
		self::assertStringEndsWith('…', $excerpt);
	}

	public function testExcerptRespectsAnExplicitMaxLength(): void {
		$note = $this->note(self::NOTES_PATH . '/a.txt', str_repeat('x', 50));

		self::assertSame(str_repeat('x', 10) . '…', $note->getExcerpt(10));
	}

	public function testEmptyNoteHasAnEmptyExcerpt(): void {
		self::assertSame('', $this->note(self::NOTES_PATH . '/a.txt', '')->getExcerpt());
	}

	/**
	 * Characterization test — this pins a bug, it does not endorse it.
	 *
	 * getExcerpt() decides whether the content repeats the title with
	 *
	 *     $length = mb_strlen($title, 'utf-8');        // characters
	 *     strncasecmp($excerpt, $title, $length)       // bytes
	 *     mb_substr($excerpt, $length, null, 'utf-8')  // characters
	 *
	 * so the comparison length is a character count handed to a byte-based
	 * function. For an ASCII title the two agree and everything works. For a
	 * multi-byte title, strncasecmp only compares the first few bytes, so
	 * content that merely *starts with the same first character* is mistaken
	 * for a repeated title and that many characters are cut off the excerpt.
	 *
	 * Below: the note is titled 日本語 and its body is "日曜日 is Sunday". Only
	 * the first character matches, but three characters are removed.
	 *
	 * Fixing it means comparing like for like — e.g. mb_strtolower() on both
	 * sides and str_starts_with(), or strlen() for the strncasecmp length.
	 */
	public function testExcerptMisdetectsARepeatedTitleForMultibyteTitles(): void {
		$note = $this->note(self::NOTES_PATH . '/日本語.md', '日曜日 is Sunday');

		self::assertSame(
			'is Sunday',
			$note->getExcerpt(),
			'the excerpt loses 日曜日 — see the docblock',
		);
	}

	public function testExcerptTitleStrippingIsCorrectForAsciiTitles(): void {
		// the control case for the test above: byte and character counts agree
		$note = $this->note(self::NOTES_PATH . '/Sunday.md', 'Sundays are quiet');

		self::assertSame('s are quiet', $note->getExcerpt());
	}

	// ---- read-only ---------------------------------------------------------

	/**
	 * @return array<string, array{0: bool, 1: bool}>
	 */
	public static function updateablePermissions(): array {
		return [
			'writable file is not read-only' => [true, false],
			'non-updateable file is read-only' => [false, true],
		];
	}

	#[DataProvider('updateablePermissions')]
	public function testReadOnlyMirrorsTheFilePermission(bool $isUpdateable, bool $expected): void {
		$file = $this->createMock(File::class);
		$file->method('getPath')->willReturn(self::NOTES_PATH . '/a.txt');
		$file->method('isUpdateable')->willReturn($isUpdateable);

		$notesFolder = $this->createMock(Folder::class);
		$notesFolder->method('getPath')->willReturn(self::NOTES_PATH);

		self::assertSame($expected, (new Note($file, $notesFolder, $this->noteUtil))->getReadOnly());
	}
}
