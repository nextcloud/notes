<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit\Service;

use OCA\Notes\Service\NoteUtil;
use OCA\Notes\Tests\Unit\NotesTestCase;
use OCP\Files\Folder;
use OCP\Files\Node;
use PHPUnit\Framework\Attributes\DataProvider;

class NoteUtilTest extends NotesTestCase {
	private NoteUtil $noteUtil;

	protected function setUp(): void {
		parent::setUp();
		$this->noteUtil = $this->createNoteUtil();
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function categoryPaths(): array {
		return [
			'nested' => ['Work/Projects', 'Work/Projects'],
			'leading slash' => ['/Work', 'Work'],
			'trailing slash' => ['Work/', 'Work'],
			'double slash collapses' => ['Work//Projects', 'Work/Projects'],
			'whitespace trimmed' => ['  Work  ', 'Work'],
			'unicode kept' => ['Grüße/日本語', 'Grüße/日本語'],
			'leading dot dropped' => ['.hidden', 'hidden'],
			'windows-illegal stripped' => ['a*b|c:d"e<f>g?h', 'abcdefgh'],
			'relative parent' => ['../../etc', 'etc'],
			'parent between names' => ['Work/../Secret', 'Work/Secret'],
			'absolute unix' => ['/etc/passwd', 'etc/passwd'],
			'backslash stripped' => ['C:\Windows', 'CWindows'],
		];
	}

	#[DataProvider('categoryPaths')]
	public function testNormalizeCategoryPath(string $input, string $expected): void {
		$normalized = $this->noteUtil->normalizeCategoryPath($input);

		self::assertSame($expected, $normalized);
		self::assertNotContains('..', explode('/', $normalized));
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function titles(): array {
		return [
			'plain' => ['Shopping list', 'Shopping list'],
			'first line only' => ["Title\nbody text", 'Title'],
			'trimmed' => ['   Title   ', 'Title'],
			'slash stripped' => ['a/b', 'ab'],
			'leading dot dropped' => ['.hidden', 'hidden'],
			'markdown kept' => ['# Heading', '# Heading'],
			'tabs become spaces' => ["A\tB", 'A B'],
			'empty falls back' => ['', 'New note'],
			'nothing usable falls back' => ['///', 'New note'],
		];
	}

	#[DataProvider('titles')]
	public function testGetSafeTitle(string $input, string $expected): void {
		self::assertSame($expected, $this->noteUtil->getSafeTitle($input));
	}

	public function testGetSafeTitleCapCountsCharactersNotBytes(): void {
		$title = $this->noteUtil->getSafeTitle(str_repeat('日', 250));

		self::assertSame(100, mb_strlen($title, 'UTF-8'));
		self::assertTrue(mb_check_encoding($title, 'UTF-8'));
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function markdown(): array {
		return [
			'atx heading' => ['# Heading', 'Heading'],
			'atx heading closed' => ['## Heading ##', 'Heading'],
			'setext underline removed' => ["Heading\n=======", "Heading\n"],
			'bullet' => ['- item', 'item'],
			'bold' => ['**bold**', 'bold'],
			'italic underscore' => ['_italic_', 'italic'],
			'inner dash kept' => ['well-known', 'well-known'],
		];
	}

	#[DataProvider('markdown')]
	public function testStripMarkdown(string $input, string $expected): void {
		self::assertSame($expected, $this->noteUtil->stripMarkdown($input));
	}

	/**
	 * @param array<string, int> $existing filename => file id already in the folder
	 */
	private function folderContaining(array $existing): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('nodeExists')
			->willReturnCallback(static fn (string $name): bool => array_key_exists($name, $existing));
		$folder->method('get')
			->willReturnCallback(function (string $name) use ($existing): Node {
				$node = $this->createMock(Node::class);
				$node->method('getId')->willReturn($existing[$name] ?? 0);
				return $node;
			});
		return $folder;
	}

	public function testGenerateFileNameUsesTheTitleWhenTheNameIsFree(): void {
		self::assertSame(
			'Title.txt',
			$this->noteUtil->generateFileName($this->folderContaining([]), 'Title', '.txt', -1),
		);
	}

	public function testGenerateFileNameKeepsTheNameOfTheNoteItself(): void {
		$folder = $this->folderContaining(['Title.txt' => 42]);

		self::assertSame('Title.txt', $this->noteUtil->generateFileName($folder, 'Title', '.txt', 42));
	}

	public function testGenerateFileNameCountsUpPastExistingSuffixes(): void {
		$folder = $this->folderContaining([
			'Title.txt' => 42,
			'Title (2).txt' => 43,
			'Title (3).txt' => 44,
		]);

		self::assertSame('Title (4).txt', $this->noteUtil->generateFileName($folder, 'Title', '.txt', 7));
	}

	public function testGenerateFileNameShortensATitleAtTheLengthCapToFitTheSuffix(): void {
		$longTitle = str_repeat('a', 100);
		$folder = $this->folderContaining([$longTitle . '.txt' => 42]);

		$filename = $this->noteUtil->generateFileName($folder, $longTitle, '.txt', 7);

		self::assertSame(str_repeat('a', 96) . ' (2).txt', $filename);
	}
}
