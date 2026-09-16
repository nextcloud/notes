<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit\Service;

use OCA\Notes\Service\Note;
use OCA\Notes\Service\NoteUtil;
use OCA\Notes\Tests\Unit\NotesTestCase;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\Attributes\DataProvider;

class NoteTest extends NotesTestCase {
	private const NOTES_PATH = '/alice/files/Notes';

	private NoteUtil $noteUtil;

	protected function setUp(): void {
		parent::setUp();
		$this->noteUtil = $this->createNoteUtil();
	}

	/**
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

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function fileNames(): array {
		return [
			'txt' => ['Shopping.txt', 'Shopping'],
			'md' => ['Shopping.md', 'Shopping'],
			'dots in name' => ['v1.2.notes.md', 'v1.2.notes'],
			'no extension' => ['Makefile', 'Makefile'],
			'unicode' => ['Grüße 日本語.md', 'Grüße 日本語'],
			'numbered collision' => ['Shopping (2).txt', 'Shopping (2)'],
		];
	}

	#[DataProvider('fileNames')]
	public function testTitleComesFromTheFileName(string $fileName, string $expected): void {
		self::assertSame($expected, $this->note(self::NOTES_PATH . '/' . $fileName)->getTitle());
	}

	public function testNoteDirectlyInTheNotesFolderHasNoCategory(): void {
		self::assertSame('', $this->note(self::NOTES_PATH . '/loose.txt')->getCategory());
	}

	public function testCategoryIsTheFullPathBelowTheNotesFolder(): void {
		self::assertSame(
			'Work/Projects/2026',
			$this->note(self::NOTES_PATH . '/Work/Projects/2026/a.txt')->getCategory(),
		);
	}

	public function testUtf8ByteOrderMarkIsStripped(): void {
		self::assertSame('# Heading', $this->note(self::NOTES_PATH . '/a.md', "\u{FEFF}# Heading")->getContent());
	}

	public function testEmptyFileOnObjectStorageReadsAsEmptyString(): void {
		// object storage returns false rather than '' for a zero-byte file
		self::assertSame('', $this->note(self::NOTES_PATH . '/a.md', false, 0)->getContent());
	}

	public function testUnreadableContentThrows(): void {
		$note = $this->note(self::NOTES_PATH . '/a.md', false, 42);

		$this->expectException(\Exception::class);
		$note->getContent();
	}

	public function testExcerptSkipsTheTitleLine(): void {
		$note = $this->note(self::NOTES_PATH . '/Shopping.txt', "Shopping\nmilk and eggs");

		self::assertSame('milk and eggs', $note->getExcerpt());
	}

	public function testExcerptStripsMarkdownAndFlattensNewlines(): void {
		$note = $this->note(self::NOTES_PATH . '/Shopping.txt', "Shopping\n- milk\n- eggs");

		self::assertSame("milk\u{2003}eggs", $note->getExcerpt());
	}

	public function testExcerptIsTruncatedWithAnEllipsis(): void {
		$excerpt = $this->note(self::NOTES_PATH . '/a.txt', str_repeat('x', 250))->getExcerpt();

		self::assertSame(101, mb_strlen($excerpt, 'utf-8'));
		self::assertStringEndsWith('…', $excerpt);
	}

	public function testEmptyNoteHasAnEmptyExcerpt(): void {
		self::assertSame('', $this->note(self::NOTES_PATH . '/a.txt', '')->getExcerpt());
	}
}
