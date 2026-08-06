<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit\Service;

use OCA\Notes\Service\NoteUtil;
use OCA\Notes\Tests\Unit\NotesTestCase;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;

class NoteUtilShareTypesTest extends NotesTestCase {
	private const OWNER = 'alice';

	/** @var array<string, array<int, list<IShare>>> keyed by folder path */
	private array $sharesInFolder = [];
	/** @var array<string, list<IShare>> keyed by "fileId:shareType" */
	private array $sharesByFile = [];

	private int $getSharesInFolderCalls = 0;
	private int $getSharesByCalls = 0;

	private NoteUtil $noteUtil;

	protected function setUp(): void {
		parent::setUp();

		$shareManager = $this->createMock(IManager::class);
		$shareManager->method('getSharesInFolder')
			->willReturnCallback(function (string $userId, Folder $folder, bool $reshares = false, bool $shallow = true): array {
				$this->getSharesInFolderCalls++;
				self::assertSame($folder->getOwner()?->getUID(), $userId);
				self::assertFalse($reshares);
				self::assertTrue($shallow);
				return $this->sharesInFolder[$folder->getPath()] ?? [];
			});
		$shareManager->method('getSharesBy')
			->willReturnCallback(function (
				string $userId,
				int $shareType,
				?Node $node = null,
				bool $reshares = false,
				int $limit = 50,
				int $offset = 0,
			): array {
				$this->getSharesByCalls++;
				self::assertFalse($reshares);
				return $this->sharesByFile[($node?->getId() ?? 0) . ':' . $shareType] ?? [];
			});

		$this->noteUtil = $this->createNoteUtil($shareManager);
	}

	/** @return Folder&MockObject */
	private function folder(string $path, bool $withOwner = true, string $owner = self::OWNER): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn($path);
		$folder->method('getOwner')->willReturn($withOwner ? $this->user($owner) : null);
		return $folder;
	}

	/** @return File&MockObject */
	private function file(int $id, string $path = '/n/note.md', ?string $owner = self::OWNER): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getPath')->willReturn($path);
		$file->method('getOwner')->willReturn($owner === null ? null : $this->user($owner));
		return $file;
	}

	/**
	 * @param list<int> $ids
	 * @return array<int, File>
	 */
	private function notesIn(Folder $folder, array $ids, ?string $owner = self::OWNER): array {
		$files = [];
		foreach ($ids as $id) {
			$files[$id] = $this->file($id, $folder->getPath() . '/note' . $id . '.md', $owner);
		}
		return $files;
	}

	/** @return IUser&MockObject */
	private function user(string $uid = self::OWNER): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		return $user;
	}

	/**
	 * @param list<int> $shareTypes
	 * @return list<IShare>
	 */
	private function shares(array $shareTypes): array {
		return array_map(function (int $type): IShare {
			$share = $this->createMock(IShare::class);
			$share->method('getShareType')->willReturn($type);
			return $share;
		}, $shareTypes);
	}

	public function testPreloadCostsOneCallPerFolderRegardlessOfNoteCount(): void {
		$root = $this->folder('/alice/files/Notes');
		$work = $this->folder('/alice/files/Notes/Work');

		$this->noteUtil->loadShareTypes([$root, $work], $this->notesIn($root, range(1, 25))
			+ $this->notesIn($work, range(26, 50)));

		self::assertSame(2, $this->getSharesInFolderCalls);
		self::assertSame(0, $this->getSharesByCalls);
	}

	public function testReadingPreloadedNotesIssuesNoFurtherQueries(): void {
		$root = $this->folder('/alice/files/Notes');
		$this->sharesInFolder['/alice/files/Notes'] = [
			7 => $this->shares([IShare::TYPE_LINK]),
		];

		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, range(1, 50)));
		$this->getSharesInFolderCalls = 0;

		for ($id = 1; $id <= 50; $id++) {
			$this->noteUtil->getShareTypes($this->file($id));
		}

		self::assertSame(0, $this->getSharesByCalls);
		self::assertSame(0, $this->getSharesInFolderCalls);
	}

	public function testPreloadedShareTypesMatchThePerFileLookup(): void {
		$expected = [IShare::TYPE_USER, IShare::TYPE_LINK, IShare::TYPE_DECK];

		foreach ($expected as $type) {
			$this->sharesByFile['1:' . $type] = $this->shares([$type]);
		}
		$perFile = $this->noteUtil->getShareTypes($this->file(1));

		$root = $this->folder('/alice/files/Notes');
		$this->sharesInFolder['/alice/files/Notes'] = [1 => $this->shares($expected)];
		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1]));

		self::assertSame($expected, $perFile);
		self::assertSame($perFile, $this->noteUtil->getShareTypes($this->file(1)));
	}

	public function testTypesAreReportedInTheDeclaredOrderAndOnlyOnce(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [
			1 => $this->shares([IShare::TYPE_DECK, IShare::TYPE_LINK, IShare::TYPE_USER, IShare::TYPE_USER]),
		];

		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1]));

		self::assertSame(
			[IShare::TYPE_USER, IShare::TYPE_LINK, IShare::TYPE_DECK],
			$this->noteUtil->getShareTypes($this->file(1)),
		);
	}

	public function testShareTypesOutsideTheReportedSetAreIgnored(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [
			1 => $this->shares([IShare::TYPE_USERGROUP, IShare::TYPE_CIRCLE, IShare::TYPE_GUEST]),
			2 => $this->shares([IShare::TYPE_USERGROUP, IShare::TYPE_GROUP]),
		];

		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1, 2]));

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(1)));
		self::assertSame([IShare::TYPE_GROUP], $this->noteUtil->getShareTypes($this->file(2)));
	}

	public function testAnUnsharedNoteIsAnsweredFromTheCache(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [1 => $this->shares([IShare::TYPE_LINK])];

		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1, 2]));

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(2)));
		self::assertSame(0, $this->getSharesByCalls);
	}

	public function testSharesOnNonNotesInTheSameFolderAreIgnored(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [
			1 => $this->shares([IShare::TYPE_LINK]),
			99 => $this->shares([IShare::TYPE_USER]),
			98 => $this->shares([IShare::TYPE_GROUP]),
		];

		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1]));

		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(1)));
	}

	public function testAFileOutsideThePreloadFallsBackToAPerFileLookup(): void {
		$root = $this->folder('/n');
		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1]));
		$this->sharesByFile['42:' . IShare::TYPE_LINK] = $this->shares([IShare::TYPE_LINK]);

		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(42)));
		self::assertGreaterThan(0, $this->getSharesByCalls);
	}

	public function testWithoutAnyPreloadEveryLookupIsPerFile(): void {
		$this->sharesByFile['1:' . IShare::TYPE_EMAIL] = $this->shares([IShare::TYPE_EMAIL]);

		self::assertSame([IShare::TYPE_EMAIL], $this->noteUtil->getShareTypes($this->file(1)));
		self::assertSame(0, $this->getSharesInFolderCalls);
	}

	public function testAFolderWithoutAnOwnerDisablesThePreload(): void {
		$ownerless = $this->folder('/n', withOwner: false);
		$this->sharesByFile['1:' . IShare::TYPE_LINK] = $this->shares([IShare::TYPE_LINK]);

		$this->noteUtil->loadShareTypes([$ownerless], $this->notesIn($ownerless, [1]));

		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(1)));
	}

	public function testAFileWithoutAnOwnerReportsNoShares(): void {
		$ownerless = $this->folder('/n', withOwner: false);
		$this->noteUtil->loadShareTypes([$ownerless], $this->notesIn($ownerless, [1]));

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(1, owner: null)));
		self::assertSame(0, $this->getSharesByCalls);
	}

	public function testFewerNotesThanFoldersIsNotWorthAPreload(): void {
		$folders = [$this->folder('/n'), $this->folder('/n/a'), $this->folder('/n/b')];
		$this->sharesByFile['1:' . IShare::TYPE_LINK] = $this->shares([IShare::TYPE_LINK]);

		$this->noteUtil->loadShareTypes($folders, $this->notesIn($folders[0], [1, 2]));

		self::assertSame(0, $this->getSharesInFolderCalls);
		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(1)));
	}

	public function testMoreNotesThanFoldersIsWorthAPreload(): void {
		$folders = [$this->folder('/n'), $this->folder('/n/a'), $this->folder('/n/b')];
		$this->sharesInFolder['/n'] = [1 => $this->shares([IShare::TYPE_LINK])];

		$this->noteUtil->loadShareTypes($folders, $this->notesIn($folders[0], range(1, 48))
			+ $this->notesIn($folders[1], [49])
			+ $this->notesIn($folders[2], [50]));

		self::assertSame(3, $this->getSharesInFolderCalls);
		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(1)));
		self::assertSame(0, $this->getSharesByCalls);
	}

	public function testAPreloadThatIsNotWorthMakingClearsAnEarlierOne(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [1 => $this->shares([IShare::TYPE_LINK])];
		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1]));

		$this->noteUtil->loadShareTypes([$root, $this->folder('/n/a')], $this->notesIn($root, [1]));

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(1)));
		self::assertSame(8, $this->getSharesByCalls);
	}

	public function testANoteOwnedBySomeoneElseIsLookedUpPerFile(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [];
		$this->sharesByFile['1:' . IShare::TYPE_USER] = $this->shares([IShare::TYPE_USER]);

		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1], owner: 'someone-else'));

		self::assertSame(
			[IShare::TYPE_USER],
			$this->noteUtil->getShareTypes($this->file(1, owner: 'someone-else')),
		);
	}

	/**
	 * A folder shared in by another user answers only for the notes inside it,
	 * so a note of the same owner sitting beside it still needs a per-file
	 * lookup rather than being reported as unshared.
	 */
	public function testANoteSharedInBesideAFolderFromTheSameOwnerIsNotReportedUnshared(): void {
		$root = $this->folder('/n');
		$bob = $this->folder('/n/bob', owner: 'bob');
		$this->sharesInFolder['/n'] = [];
		$this->sharesInFolder['/n/bob'] = [2 => $this->shares([IShare::TYPE_USER])];
		$this->sharesByFile['1:' . IShare::TYPE_USER] = $this->shares([IShare::TYPE_USER]);

		$this->noteUtil->loadShareTypes(
			[$root, $bob],
			$this->notesIn($root, [1], owner: 'bob') + $this->notesIn($bob, [2], owner: 'bob'),
		);

		self::assertSame([IShare::TYPE_USER], $this->noteUtil->getShareTypes($this->file(1, owner: 'bob')));
		self::assertGreaterThan(0, $this->getSharesByCalls);

		$this->getSharesByCalls = 0;
		self::assertSame([IShare::TYPE_USER], $this->noteUtil->getShareTypes($this->file(2, owner: 'bob')));
		self::assertSame(0, $this->getSharesByCalls);
	}

	public function testASecondPreloadReplacesTheFirst(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [1 => $this->shares([IShare::TYPE_LINK])];
		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1]));
		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(1)));

		$this->sharesInFolder['/n'] = [];
		$this->noteUtil->loadShareTypes([$root], $this->notesIn($root, [1]));

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(1)));
	}
}
