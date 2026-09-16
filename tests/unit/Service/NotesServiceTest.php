<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit\Service;

use OCA\Notes\Service\MetaService;
use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\SettingsService;
use OCA\Notes\Tests\Unit\NotesTestCase;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IFilenameValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

class NotesServiceTest extends NotesTestCase {
	private NotesService $notesService;
	private int $nextFileId = 100;

	protected function setUp(): void {
		parent::setUp();

		$this->notesService = new NotesService(
			$this->createMock(MetaService::class),
			$this->createMock(SettingsService::class),
			$this->createNoteUtil(),
			$this->createMock(IFilenameValidator::class),
		);
	}

	/**
	 * Builds a mocked folder tree. An array value is a subfolder, a string
	 * value is a file name.
	 *
	 * @param array<int|string, string|array<int|string, mixed>> $spec
	 * @return Folder&MockObject
	 */
	private function folder(array $spec): Folder {
		$children = [];
		foreach ($spec as $name => $value) {
			if (is_array($value)) {
				$sub = $this->folder($value);
				$sub->method('getName')->willReturn((string)$name);
				$children[] = $sub;
			} else {
				$children[] = $this->file($value);
			}
		}

		$folder = $this->createMock(Folder::class);
		$folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
		$folder->method('getDirectoryListing')->willReturn($children);
		return $folder;
	}

	/** @return File&MockObject */
	private function file(string $name): File {
		$file = $this->createMock(File::class);
		$file->method('getType')->willReturn(FileInfo::TYPE_FILE);
		$file->method('getName')->willReturn($name);
		$file->method('getId')->willReturn($this->nextFileId++);
		return $file;
	}

	/**
	 * @param array<int|string, string|array<int|string, mixed>> $spec
	 * @return array{files: array<int, File>, categories: list<string>}
	 */
	private function gather(array $spec, string $customExtension = 'md', bool $showHidden = false): array {
		$method = new \ReflectionMethod(NotesService::class, 'gatherNoteFiles');
		/** @var array{files: array<int, File>, categories: list<string>} $result */
		$result = $method->invoke(null, $customExtension, $this->folder($spec), $showHidden);
		return $result;
	}

	/**
	 * @param array<int|string, string|array<int|string, mixed>> $spec
	 * @return list<string>
	 */
	private function gatheredNames(array $spec, string $customExtension = 'md', bool $showHidden = false): array {
		$names = array_map(
			static fn (File $f): string => $f->getName(),
			$this->gather($spec, $customExtension, $showHidden)['files'],
		);
		sort($names);
		return array_values($names);
	}

	public function testRecognisesTheBuiltInNoteExtensions(): void {
		self::assertSame(
			['a.markdown', 'b.md', 'c.note', 'd.org', 'e.txt'],
			$this->gatheredNames(['a.markdown', 'b.md', 'c.note', 'd.org', 'e.txt']),
		);
	}

	public function testIgnoresFilesThatAreNotNotes(): void {
		self::assertSame(
			['keep.md'],
			$this->gatheredNames(['keep.md', 'photo.jpg', 'report.pdf', 'archive.zip', 'noextension']),
		);
	}

	public function testExtensionMatchingIsCaseInsensitive(): void {
		self::assertSame(['LOUD.TXT', 'Mixed.Md'], $this->gatheredNames(['LOUD.TXT', 'Mixed.Md']));
	}

	public function testHonoursTheUsersCustomExtension(): void {
		self::assertSame(
			['note.adoc', 'plain.txt'],
			$this->gatheredNames(['note.adoc', 'plain.txt', 'other.rst'], 'adoc'),
		);
	}

	public function testCollectsNotesFromEverySubfolder(): void {
		self::assertSame(
			['deep.md', 'nested.txt', 'top.txt'],
			$this->gatheredNames([
				'top.txt',
				'Work' => [
					'nested.txt',
					'Projects' => ['deep.md'],
				],
			]),
		);
	}

	public function testHiddenNotesAreOnlyCollectedWhenRequested(): void {
		self::assertSame(['shown.md'], $this->gatheredNames(['shown.md', '.hidden.md']));
		self::assertSame(['.hidden.md', 'shown.md'], $this->gatheredNames(['shown.md', '.hidden.md'], 'md', true));
	}

	public function testAttachmentFoldersAreNotCategories(): void {
		$categories = $this->gather(['.attachments.42' => [], 'Work' => []], 'md', true)['categories'];

		self::assertSame(['Work'], array_values($categories));
	}

	public function testCollectsCategoriesIncludingFoldersWithoutNotes(): void {
		$categories = $this->gather([
			'loose.txt',
			'Work' => ['a.txt'],
			'Empty' => [],
		])['categories'];

		self::assertSame(['Work', 'Empty'], array_values($categories));
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function contents(): array {
		return [
			'plain first line' => ["Shopping\nmilk", 'Shopping'],
			'heading becomes title' => ["# Shopping\nmilk", 'Shopping'],
			'bullet becomes title' => ["- Shopping\n- milk", 'Shopping'],
			'bold is unwrapped' => ['**Shopping**', 'Shopping'],
			'empty falls back' => ['', 'New note'],
			'slash is removed' => ['a/b', 'ab'],
		];
	}

	#[DataProvider('contents')]
	public function testGetTitleFromContent(string $content, string $expected): void {
		self::assertSame($expected, $this->notesService->getTitleFromContent($content));
	}
}
