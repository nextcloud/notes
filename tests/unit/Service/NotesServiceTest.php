<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit\Service;

use OCA\Notes\Service\MetaService;
use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\NoteUtil;
use OCA\Notes\Service\SettingsService;
use OCA\Notes\Service\TagService;
use OCA\Notes\Service\Util;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Share\IManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Walking the notes folder decides two things: which files count as notes, and
 * which categories exist. Both are pure tree logic once the Folder API is
 * mocked, and both are load-bearing — a file wrongly excluded is a note the
 * user cannot see.
 */
class NotesServiceTest extends TestCase {
	private NotesService $notesService;

	protected function setUp(): void {
		parent::setUp();

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$db = $this->createMock(IDBConnection::class);
		$db->method('supports4ByteText')->willReturn(true);

		$noteUtil = new NoteUtil(
			new Util($l10n, $this->createMock(LoggerInterface::class)),
			$this->createMock(IRootFolder::class),
			$db,
			$this->createMock(TagService::class),
			$this->createMock(IManager::class),
			$this->createMock(IUserSession::class),
			$this->createMock(SettingsService::class),
		);

		$this->notesService = new NotesService(
			$this->createMock(MetaService::class),
			$this->createMock(SettingsService::class),
			$noteUtil,
		);
	}

	// ---- tree fixtures -----------------------------------------------------

	private int $nextFileId = 100;

	/**
	 * Builds a mocked folder tree. An array value is a subfolder, a string
	 * value is a file name (the id is assigned automatically).
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
	 * The default mirrors production: SettingsService's `customSuffix`
	 * validator always yields a non-empty suffix (falling back to '.md'), and
	 * getCustomExtension() strips the leading dot. Passing '' here would not be
	 * a reachable state — see
	 * {@see testAnEmptyCustomExtensionMatchesEveryExtensionlessFile}.
	 *
	 * @param array<int|string, string|array<int|string, mixed>> $spec
	 * @return array{files: array<int, File>, categories: list<string>}
	 */
	private function gather(array $spec, string $customExtension = 'md'): array {
		$method = new \ReflectionMethod(NotesService::class, 'gatherNoteFiles');
		/** @var array{files: array<int, File>, categories: list<string>} $result */
		$result = $method->invoke(null, $customExtension, $this->folder($spec));
		return $result;
	}

	/**
	 * @param array<int|string, string|array<int|string, mixed>> $spec
	 * @return list<string>
	 */
	private function gatheredNames(array $spec, string $customExtension = 'md'): array {
		$names = array_map(
			static fn (File $f): string => $f->getName(),
			$this->gather($spec, $customExtension)['files'],
		);
		sort($names);
		return array_values($names);
	}

	// ---- which files count as notes ----------------------------------------

	public function testRecognisesTheBuiltInNoteExtensions(): void {
		self::assertSame(
			['a.markdown', 'b.md', 'c.note', 'd.org', 'e.txt'],
			$this->gatheredNames([
				'a.markdown', 'b.md', 'c.note', 'd.org', 'e.txt',
			]),
		);
	}

	public function testIgnoresFilesThatAreNotNotes(): void {
		self::assertSame(
			['keep.md'],
			$this->gatheredNames([
				'keep.md', 'photo.jpg', 'report.pdf', 'archive.zip', 'noextension',
			]),
		);
	}

	public function testExtensionMatchingIsCaseInsensitive(): void {
		self::assertSame(
			['LOUD.TXT', 'Mixed.Md'],
			$this->gatheredNames(['LOUD.TXT', 'Mixed.Md']),
		);
	}

	public function testHonoursTheUsersCustomExtension(): void {
		self::assertSame(
			['note.adoc', 'plain.txt'],
			$this->gatheredNames(['note.adoc', 'plain.txt', 'other.rst'], 'adoc'),
		);
	}

	/**
	 * A trap worth pinning: isNote() compares the file's extension against the
	 * custom one with `$ext === $customExtension`, and pathinfo() yields '' for
	 * a file without an extension. An empty custom extension therefore makes
	 * every extensionless file a note.
	 *
	 * That state is not reachable today — SettingsService's `customSuffix`
	 * validator falls back to '.md' and getCustomExtension() only strips the
	 * leading dot — but the guard lives in a different class from the
	 * comparison, so this records the coupling.
	 */
	public function testAnEmptyCustomExtensionMatchesEveryExtensionlessFile(): void {
		self::assertSame(
			['keep.md'],
			$this->gatheredNames(['keep.md', 'Makefile'], 'md'),
			'with a real custom extension an extensionless file is not a note',
		);
		self::assertSame(
			['Makefile', 'keep.md'],
			$this->gatheredNames(['keep.md', 'Makefile'], ''),
			'with an empty one it is — SettingsService is what prevents this',
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
			'notes must be found at any depth',
		);
	}

	public function testFilesAreKeyedByFileId(): void {
		$files = $this->gather(['one.txt', 'two.txt'])['files'];

		foreach ($files as $id => $file) {
			self::assertSame($id, $file->getId(), 'the array key must be the file id');
		}
	}

	// ---- folders (needed for the bulk share lookup) -------------------------

	/**
	 * NoteUtil::loadShareTypes() needs every folder of the tree, because
	 * IManager::getSharesInFolder() only reports on a folder's direct children
	 * (the server rejects $shallow = false). Missing a folder here would mean
	 * silently losing the shared indicator for the notes inside it.
	 */
	public function testTheWalkReportsEveryFolderIncludingTheNotesFolderItself(): void {
		$result = $this->gather([
			'top.txt',
			'Work' => [
				'a.txt',
				'Projects' => [
					'2026' => ['deep.md'],
				],
			],
			'Personal' => [],
		]);

		self::assertCount(
			5,
			$result['folders'],
			'the notes folder plus Work, Work/Projects, Work/Projects/2026 and Personal',
		);
		self::assertContainsOnlyInstancesOf(Folder::class, $result['folders']);
	}

	public function testFoldersIsAListWithNoGapsSoEveryEntryIsIterated(): void {
		// this is the counterpart to the categories bug below: 'folders' is
		// merged with array_merge(), so nested entries survive
		$result = $this->gather([
			'Work' => ['Projects' => []],
			'Personal' => ['Recipes' => []],
		]);

		self::assertSame(
			range(0, count($result['folders']) - 1),
			array_keys($result['folders']),
			'a "+" union here would drop nested folders and skip their shares',
		);
		self::assertCount(5, $result['folders']);
	}

	// ---- categories --------------------------------------------------------

	public function testCollectsTopLevelCategories(): void {
		$categories = $this->gather([
			'loose.txt',
			'Work' => ['a.txt'],
			'Personal' => ['b.txt'],
		])['categories'];

		self::assertSame(['Work', 'Personal'], array_values($categories));
	}

	public function testAFolderWithoutNotesIsStillACategory(): void {
		// this is the whole reason the server sends a category list at all: an
		// empty folder has no note to derive the category from
		$categories = $this->gather(['Empty' => []])['categories'];

		self::assertSame(['Empty'], array_values($categories));
	}

	/**
	 * Characterization test — this pins a bug, it does not endorse it.
	 *
	 * gatherNoteFiles() merges the recursion's categories with `+`:
	 *
	 *     $data['categories'] = $data['categories'] + $data_sub['categories'];
	 *
	 * Both operands are sequentially-keyed lists, so the union keeps the
	 * left-hand value for every index that is already occupied and silently
	 * discards the rest. `array_merge()` is the fix.
	 *
	 * Which subcategories are lost therefore depends on position, not on depth:
	 * a subfolder is dropped only while the parent's list is already at least as
	 * long as the recursion's. That makes the outcome look arbitrary --- see
	 * {@see testWhichNestedCategoriesSurviveDependsOnSiblingOrder}, where
	 * 'Work/A' vanishes but its siblings 'Work/B' and 'Work/C' do not.
	 *
	 * Visible effect today: a nested folder that *contains* notes still appears
	 * in the UI, because the frontend derives categories from the notes
	 * themselves and only consults this list for folders that have none. So the
	 * symptom is an empty nested subcategory missing from the sidebar. The v1
	 * API does not expose this list, so third-party clients are unaffected.
	 *
	 * When this is fixed, the expectation below becomes
	 * ['Work', 'Work/Projects', 'Work/Projects/2026', 'Personal', 'Personal/Recipes'].
	 */
	public function testNestedCategoriesAreCurrentlyDropped(): void {
		$categories = $this->gather([
			'Work' => [
				'Projects' => [
					'2026' => [],
				],
			],
			'Personal' => [
				'Recipes' => [],
			],
		])['categories'];

		self::assertSame(
			['Work', 'Personal'],
			array_values($categories),
			'nested subcategories are lost to the "+" array union — see the docblock',
		);
	}

	/**
	 * The companion to the test above, and the reason the bug is easy to
	 * misread as "nesting is unsupported": the union only loses an entry whose
	 * index is already taken, so a folder with more children than the parent
	 * has accumulated keeps the later ones. Here 'Work' occupies index 0, so
	 * 'Work/A' (index 0 of the recursion) is dropped while 'Work/B' and
	 * 'Work/C' survive.
	 *
	 * With array_merge() the expectation becomes
	 * ['Work', 'Work/A', 'Work/B', 'Work/C'].
	 */
	public function testWhichNestedCategoriesSurviveDependsOnSiblingOrder(): void {
		$categories = $this->gather([
			'Work' => [
				'A' => [],
				'B' => [],
				'C' => [],
			],
		])['categories'];

		self::assertSame(
			['Work', 'Work/B', 'Work/C'],
			array_values($categories),
			'the first sibling collides with the parent index and is lost',
		);
	}

	/**
	 * The counterpart to the test above: the *files* union is keyed by file id,
	 * which is unique across the tree, so `+` is correct there and no note is
	 * lost. This is what makes the categories case a slip rather than a
	 * misunderstanding, and it must keep working if the merge is changed.
	 */
	public function testNoNoteIsLostByTheFileUnionAcrossSiblingFolders(): void {
		self::assertSame(
			['a.txt', 'b.txt', 'c.txt', 'd.txt'],
			$this->gatheredNames([
				'Work' => [
					'a.txt',
					'Deep' => ['b.txt'],
				],
				'Personal' => [
					'c.txt',
					'Deeper' => ['d.txt'],
				],
			]),
		);
	}

	// ---- title derivation --------------------------------------------------

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
