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
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NoteUtilShareTypesTest extends TestCase {
	private const OWNER = 'alice';

	private IManager&MockObject $shareManager;

	/** getSharesInFolder() results, keyed by the folder's path */
	private array $sharesInFolder = [];
	/** getSharesBy() results, keyed by "fileId:shareType" */
	private array $sharesByFile = [];

	private int $getSharesInFolderCalls = 0;
	private int $getSharesByCalls = 0;

	private NoteUtil $noteUtil;

	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(IManager::class);
		$this->shareManager->method('getSharesInFolder')
			->willReturnCallback(function (string $userId, Folder $folder, bool $reshares = false, bool $shallow = true): array {
				$this->getSharesInFolderCalls++;
				self::assertSame(self::OWNER, $userId, 'shares must be looked up as the folder owner');
				self::assertFalse($reshares, 'reshares would report shares the owner did not make');
				self::assertTrue($shallow, 'the server rejects a non-shallow lookup');
				return $this->sharesInFolder[$folder->getPath()] ?? [];
			});
		$this->shareManager->method('getSharesBy')
			->willReturnCallback(function (
				string $userId,
				int $shareType,
				?Node $node = null,
				bool $reshares = false,
				int $limit = 50,
				int $offset = 0,
			): array {
				$this->getSharesByCalls++;
				self::assertFalse($reshares, 'reshares would report shares the owner did not make');
				$key = ($node?->getId() ?? 0) . ':' . $shareType;
				return $this->sharesByFile[$key] ?? [];
			});

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$db = $this->createMock(IDBConnection::class);
		$db->method('supports4ByteText')->willReturn(true);

		$this->noteUtil = new NoteUtil(
			new Util($l10n, $this->createMock(LoggerInterface::class)),
			$this->createMock(IRootFolder::class),
			$db,
			$this->createMock(TagService::class),
			$this->shareManager,
			$this->createMock(IUserSession::class),
			$this->createMock(SettingsService::class),
		);
	}

	// ---- fixtures ----------------------------------------------------------

	/** @return Folder&MockObject */
	private function folder(string $path, bool $withOwner = true): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn($path);
		$folder->method('getOwner')->willReturn($withOwner ? $this->user() : null);
		return $folder;
	}

	/** @return File&MockObject */
	private function file(int $id, ?string $owner = self::OWNER): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getOwner')->willReturn($owner === null ? null : $this->user($owner));
		return $file;
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

	// ---- the query count ---------------------------------------------------

	public function testPreloadCostsOneCallPerFolderRegardlessOfNoteCount(): void {
		$root = $this->folder('/alice/files/Notes');
		$work = $this->folder('/alice/files/Notes/Work');

		$this->noteUtil->loadShareTypes([$root, $work], range(1, 50));

		self::assertSame(2, $this->getSharesInFolderCalls, 'one bulk call per folder');
		self::assertSame(0, $this->getSharesByCalls, 'no per-file query during preload');
	}

	public function testReadingPreloadedNotesIssuesNoFurtherQueries(): void {
		$root = $this->folder('/alice/files/Notes');
		$this->sharesInFolder['/alice/files/Notes'] = [
			7 => $this->shares([IShare::TYPE_LINK]),
		];

		$this->noteUtil->loadShareTypes([$root], range(1, 50));
		$this->getSharesInFolderCalls = 0;

		for ($id = 1; $id <= 50; $id++) {
			$this->noteUtil->getShareTypes($this->file($id));
		}

		self::assertSame(0, $this->getSharesByCalls, '50 notes must not trigger 400 queries');
		self::assertSame(0, $this->getSharesInFolderCalls);
	}

	public function testQueryCountTracksFoldersNotNotes(): void {
		$folders = [$this->folder('/f0'), $this->folder('/f1'), $this->folder('/f2')];

		$this->noteUtil->loadShareTypes($folders, range(1, 500));
		for ($id = 1; $id <= 500; $id++) {
			$this->noteUtil->getShareTypes($this->file($id));
		}

		self::assertSame(count($folders), $this->getSharesInFolderCalls);
		self::assertSame(0, $this->getSharesByCalls);
	}

	// ---- the values are unchanged ------------------------------------------

	public function testPreloadedShareTypesMatchThePerFileLookup(): void {
		$expected = [IShare::TYPE_USER, IShare::TYPE_LINK, IShare::TYPE_DECK];

		// per-file path
		foreach ($expected as $type) {
			$this->sharesByFile['1:' . $type] = $this->shares([$type]);
		}
		$perFile = $this->noteUtil->getShareTypes($this->file(1));

		// bulk path, same shares
		$root = $this->folder('/alice/files/Notes');
		$this->sharesInFolder['/alice/files/Notes'] = [1 => $this->shares($expected)];
		$this->noteUtil->loadShareTypes([$root], [1]);
		$preloaded = $this->noteUtil->getShareTypes($this->file(1));

		self::assertSame($expected, $perFile);
		self::assertSame($perFile, $preloaded, 'the payload must not depend on how shares were fetched');
	}

	public function testTypesAreReportedInTheDeclaredOrderNotTheProvidersOrder(): void {
		$root = $this->folder('/n');
		// providers answer in their own order
		$this->sharesInFolder['/n'] = [
			1 => $this->shares([IShare::TYPE_DECK, IShare::TYPE_LINK, IShare::TYPE_USER]),
		];

		$this->noteUtil->loadShareTypes([$root], [1]);

		self::assertSame(
			[IShare::TYPE_USER, IShare::TYPE_LINK, IShare::TYPE_DECK],
			$this->noteUtil->getShareTypes($this->file(1)),
		);
	}

	public function testRepeatedSharesOfOneTypeAreReportedOnce(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [
			1 => $this->shares([IShare::TYPE_USER, IShare::TYPE_USER, IShare::TYPE_USER]),
		];

		$this->noteUtil->loadShareTypes([$root], [1]);

		self::assertSame([IShare::TYPE_USER], $this->noteUtil->getShareTypes($this->file(1)));
	}

	public function testShareTypesOutsideTheReportedSetAreIgnored(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [
			1 => $this->shares([IShare::TYPE_USERGROUP, IShare::TYPE_CIRCLE, IShare::TYPE_GUEST]),
			2 => $this->shares([IShare::TYPE_USERGROUP, IShare::TYPE_GROUP]),
		];

		$this->noteUtil->loadShareTypes([$root], [1, 2]);

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(1)));
		self::assertSame([IShare::TYPE_GROUP], $this->noteUtil->getShareTypes($this->file(2)));
	}

	public function testAnUnsharedNoteIsAnsweredFromTheCache(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [1 => $this->shares([IShare::TYPE_LINK])];

		$this->noteUtil->loadShareTypes([$root], [1, 2]);

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(2)));
		self::assertSame(
			0,
			$this->getSharesByCalls,
			'"not shared" is a real answer and must not fall back to per-file queries',
		);
	}

	public function testSharesOnNonNotesInTheSameFolderAreIgnored(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [
			1 => $this->shares([IShare::TYPE_LINK]),
			// a shared PDF and a shared subfolder living next to the notes
			99 => $this->shares([IShare::TYPE_USER]),
			98 => $this->shares([IShare::TYPE_GROUP]),
		];

		$this->noteUtil->loadShareTypes([$root], [1]);

		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(1)));
	}

	// ---- fallbacks ---------------------------------------------------------

	public function testAFileOutsideThePreloadStillFallsBackToAPerFileLookup(): void {
		$root = $this->folder('/n');
		$this->noteUtil->loadShareTypes([$root], [1]);
		$this->sharesByFile['42:' . IShare::TYPE_LINK] = $this->shares([IShare::TYPE_LINK]);

		// the single-note endpoints never preload a tree
		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(42)));
		self::assertGreaterThan(0, $this->getSharesByCalls);
	}

	public function testWithoutAnyPreloadEveryLookupIsPerFile(): void {
		$this->sharesByFile['1:' . IShare::TYPE_EMAIL] = $this->shares([IShare::TYPE_EMAIL]);

		self::assertSame([IShare::TYPE_EMAIL], $this->noteUtil->getShareTypes($this->file(1)));
		self::assertSame(0, $this->getSharesInFolderCalls);
	}

	public function testAFolderWithoutAnOwnerDisablesThePreloadInsteadOfReportingNotShared(): void {
		$ownerless = $this->folder('/n', withOwner: false);
		$this->sharesByFile['1:' . IShare::TYPE_LINK] = $this->shares([IShare::TYPE_LINK]);

		$this->noteUtil->loadShareTypes([$ownerless], [1]);

		self::assertSame(
			[IShare::TYPE_LINK],
			$this->noteUtil->getShareTypes($this->file(1)),
			'the share must still be found via the per-file path',
		);
	}

	public function testAFileWithoutAnOwnerReportsNoSharesInsteadOfFailing(): void {
		$this->noteUtil->loadShareTypes([$this->folder('/n', withOwner: false)], [1]);

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(1, owner: null)));
		self::assertSame(0, $this->getSharesByCalls, 'there is no user to ask about shares');
	}

	public function testFewerNotesThanFoldersIsNotWorthAPreload(): void {
		$folders = [$this->folder('/n'), $this->folder('/n/a'), $this->folder('/n/b')];
		$this->sharesByFile['1:' . IShare::TYPE_LINK] = $this->shares([IShare::TYPE_LINK]);

		$this->noteUtil->loadShareTypes($folders, [1, 2]);

		self::assertSame(0, $this->getSharesInFolderCalls, 'three folders cost more than two notes');
		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(1)));
	}

	public function testMoreNotesThanFoldersIsWorthAPreload(): void {
		$folders = [$this->folder('/n'), $this->folder('/n/a'), $this->folder('/n/b')];
		$this->sharesInFolder['/n'] = [1 => $this->shares([IShare::TYPE_LINK])];

		$this->noteUtil->loadShareTypes($folders, range(1, 50));

		self::assertSame(3, $this->getSharesInFolderCalls);
		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(1)));
		self::assertSame(0, $this->getSharesByCalls, 'the cache answered without a per-file lookup');
	}

	public function testAPreloadThatIsNotWorthMakingClearsAnEarlierOne(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [1 => $this->shares([IShare::TYPE_LINK])];
		$this->noteUtil->loadShareTypes([$root], [1]);

		$this->noteUtil->loadShareTypes([$root, $this->folder('/n/a')], [1]);

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(1)));
		self::assertSame(8, $this->getSharesByCalls, 'the stale cache must not answer');
	}

	public function testANoteOwnedBySomeoneElseIsLookedUpPerFile(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [];
		$this->sharesByFile['1:' . IShare::TYPE_USER] = $this->shares([IShare::TYPE_USER]);

		$this->noteUtil->loadShareTypes([$root], [1]);

		self::assertSame(
			[IShare::TYPE_USER],
			$this->noteUtil->getShareTypes($this->file(1, owner: 'someone-else')),
			'the preload only covers notes owned by the user it queried as',
		);
	}

	public function testASecondPreloadReplacesTheFirst(): void {
		$root = $this->folder('/n');
		$this->sharesInFolder['/n'] = [1 => $this->shares([IShare::TYPE_LINK])];
		$this->noteUtil->loadShareTypes([$root], [1]);
		self::assertSame([IShare::TYPE_LINK], $this->noteUtil->getShareTypes($this->file(1)));

		// e.g. a share was removed between two calls within one request
		$this->sharesInFolder['/n'] = [];
		$this->noteUtil->loadShareTypes([$root], [1]);

		self::assertSame([], $this->noteUtil->getShareTypes($this->file(1)));
	}
}
