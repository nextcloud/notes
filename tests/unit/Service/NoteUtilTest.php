<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit\Service;

use OCA\Notes\Service\NoteUtil;
use OCA\Notes\Service\SettingsService;
use OCA\Notes\Service\TagService;
use OCA\Notes\Service\Util;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Share\IManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * NoteUtil turns user input into file and folder names, which makes it the
 * app's trust boundary: a category or title reaches the filesystem through
 * here. The path tests below are the ones that matter — every note lives in the
 * user's own storage, so a component that escaped the notes folder would let a
 * note be written anywhere the user's account can reach.
 */
class NoteUtilTest extends TestCase {
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

	// ---- category paths ----------------------------------------------------

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function categoryPaths(): array {
		return [
			'plain' => ['Work', 'Work'],
			'nested' => ['Work/Projects', 'Work/Projects'],
			'deeply nested' => ['a/b/c/d', 'a/b/c/d'],
			'empty' => ['', ''],
			'root slash' => ['/', ''],
			'leading slash' => ['/Work', 'Work'],
			'trailing slash' => ['Work/', 'Work'],
			'double slash collapses' => ['Work//Projects', 'Work/Projects'],
			'surrounding whitespace trimmed' => ['  Work  ', 'Work'],
			'inner whitespace kept' => ['My Notes', 'My Notes'],
			'unicode kept' => ['Grüße/日本語', 'Grüße/日本語'],
			// a leading dot would create a hidden folder
			'leading dot dropped' => ['.hidden', 'hidden'],
			'leading dots dropped' => ['...hidden', 'hidden'],
			'inner dot kept' => ['my.notes', 'my.notes'],
			// characters that are illegal on at least one supported platform
			'windows-illegal stripped' => ['a*b|c:d"e<f>g?h', 'abcdefgh'],
			'backslash stripped' => ['Work\\Projects', 'WorkProjects'],
		];
	}

	#[DataProvider('categoryPaths')]
	public function testNormalizeCategoryPath(string $input, string $expected): void {
		self::assertSame($expected, $this->noteUtil->normalizeCategoryPath($input));
	}

	/**
	 * Traversal attempts must not survive normalisation. Each component is
	 * sanitised individually, so a '..' component loses its dots and is then
	 * dropped as empty — it must never be emitted, and must never consume the
	 * component in front of it either.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function traversalPaths(): array {
		return [
			'relative parent' => ['../../etc', 'etc'],
			'parent only' => ['..', ''],
			'many parents' => ['../../..', ''],
			'parent between names' => ['Work/../Secret', 'Work/Secret'],
			'current dir' => ['./Work', 'Work'],
			'absolute unix' => ['/etc/passwd', 'etc/passwd'],
			'absolute-ish windows' => ['C:\\Windows\\System32', 'CWindowsSystem32'],
			'dot dot slash repeated' => ['..././..', ''],
			'encoded-looking' => ['%2e%2e/Work', '%2e%2e/Work'],
			'null-ish name' => ['..	', ''],
		];
	}

	#[DataProvider('traversalPaths')]
	public function testNormalizeCategoryPathBlocksTraversal(string $input, string $expected): void {
		$normalized = $this->noteUtil->normalizeCategoryPath($input);

		self::assertSame($expected, $normalized);
		self::assertNotContains(
			'..',
			explode('/', $normalized),
			'no component may be a parent reference after normalisation',
		);
	}

	// ---- titles -----------------------------------------------------------

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function titles(): array {
		return [
			'plain' => ['Shopping list', 'Shopping list'],
			'first line only' => ["Title\nbody text", 'Title'],
			'first line only (crlf)' => ["Title\r\nbody text", 'Title'],
			'first line only (cr)' => ["Title\rbody text", 'Title'],
			'trimmed' => ['   Title   ', 'Title'],
			'slash stripped' => ['a/b', 'ab'],
			'leading dot dropped' => ['.hidden', 'hidden'],
			'markdown is not stripped here' => ['# Heading', '# Heading'],
			'tabs become spaces' => ["A\tB", 'A B'],
			'nbsp becomes space' => ["A\u{00A0}B", 'A B'],
			'unicode kept' => ['Grüße 日本語', 'Grüße 日本語'],
		];
	}

	#[DataProvider('titles')]
	public function testGetSafeTitle(string $input, string $expected): void {
		self::assertSame($expected, $this->noteUtil->getSafeTitle($input));
	}

	public function testGetSafeTitleFallsBackWhenNothingUsableIsLeft(): void {
		self::assertSame('New note', $this->noteUtil->getSafeTitle(''));
		self::assertSame('New note', $this->noteUtil->getSafeTitle('   '));
		self::assertSame('New note', $this->noteUtil->getSafeTitle('///'));
		self::assertSame('New note', $this->noteUtil->getSafeTitle('...'));
	}

	/**
	 * Characterization test, not an endorsement: getSafeTitle() guards the
	 * fallback with empty(), and empty('0') is true in PHP. A note whose first
	 * line is exactly "0" is therefore titled "New note" instead of "0".
	 * Pinned so the behaviour change is visible if the guard is ever tightened
	 * to a strict comparison.
	 */
	public function testSingleZeroTitleFallsBackToNewNote(): void {
		self::assertSame('New note', $this->noteUtil->getSafeTitle('0'));
		self::assertSame('0.', $this->noteUtil->getSafeTitle('0.'), 'only a bare zero is affected');
		self::assertSame('00', $this->noteUtil->getSafeTitle('00'));
	}

	public function testGetSafeTitleIsCappedAtOneHundredCharacters(): void {
		$title = $this->noteUtil->getSafeTitle(str_repeat('a', 250));

		self::assertSame(100, mb_strlen($title, 'UTF-8'));
	}

	public function testGetSafeTitleCapCountsCharactersNotBytes(): void {
		// 250 three-byte characters: a byte-based cap would cut mid-character
		// and produce invalid UTF-8
		$title = $this->noteUtil->getSafeTitle(str_repeat('日', 250));

		self::assertSame(100, mb_strlen($title, 'UTF-8'));
		self::assertTrue(mb_check_encoding($title, 'UTF-8'), 'title must stay valid UTF-8');
	}

	// ---- markdown stripping -----------------------------------------------

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function markdown(): array {
		return [
			'atx heading' => ['# Heading', 'Heading'],
			'atx heading closed' => ['## Heading ##', 'Heading'],
			'setext underline removed' => ["Heading\n=======", "Heading\n"],
			'bullet dash' => ['- item', 'item'],
			'bullet star' => ['* item', 'item'],
			'bullet plus' => ['+ item', 'item'],
			'bold' => ['**bold**', 'bold'],
			'italic underscore' => ['_italic_', 'italic'],
			'plain text untouched' => ['just text', 'just text'],
			'inner dash kept' => ['well-known', 'well-known'],
		];
	}

	#[DataProvider('markdown')]
	public function testStripMarkdown(string $input, string $expected): void {
		self::assertSame($expected, $this->noteUtil->stripMarkdown($input));
	}

	// ---- file name generation ---------------------------------------------

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
		// renaming a note to the title it already has must not add a suffix
		$folder = $this->folderContaining(['Title.txt' => 42]);

		self::assertSame('Title.txt', $this->noteUtil->generateFileName($folder, 'Title', '.txt', 42));
	}

	public function testGenerateFileNameAvoidsOverwritingADifferentNote(): void {
		$folder = $this->folderContaining(['Title.txt' => 42]);

		self::assertSame('Title (2).txt', $this->noteUtil->generateFileName($folder, 'Title', '.txt', 7));
	}

	public function testGenerateFileNameCountsUpPastExistingSuffixes(): void {
		$folder = $this->folderContaining([
			'Title.txt' => 42,
			'Title (2).txt' => 43,
			'Title (3).txt' => 44,
		]);

		self::assertSame('Title (4).txt', $this->noteUtil->generateFileName($folder, 'Title', '.txt', 7));
	}

	public function testGenerateFileNameIncrementsAnExplicitlyNumberedTitle(): void {
		$folder = $this->folderContaining(['Title (2).txt' => 42]);

		self::assertSame('Title (3).txt', $this->noteUtil->generateFileName($folder, 'Title (2)', '.txt', 7));
	}

	/**
	 * A title already at the 100-character cap has no room for the " (2)"
	 * suffix. If the suffix were simply appended, getSafeTitle() would trim it
	 * straight back off and the collision path would recurse for ever, so the
	 * base title has to be shortened to make room.
	 */
	public function testGenerateFileNameTerminatesOnAMaximumLengthTitle(): void {
		$longTitle = str_repeat('a', 100);
		$folder = $this->folderContaining([$longTitle . '.txt' => 42]);

		$filename = $this->noteUtil->generateFileName($folder, $longTitle, '.txt', 7);

		self::assertSame(str_repeat('a', 96) . ' (2).txt', $filename);
		self::assertSame(100, mb_strlen(pathinfo($filename, PATHINFO_FILENAME), 'UTF-8'));
	}

	public function testGenerateFileNameSanitisesBeforeCheckingForCollisions(): void {
		// the slash must be gone before the name is looked up, or the lookup
		// would ask the folder about a path rather than a name
		$folder = $this->folderContaining([]);

		self::assertSame(
			'ab.md',
			$this->noteUtil->generateFileName($folder, 'a/b', '.md', -1),
		);
	}
}
