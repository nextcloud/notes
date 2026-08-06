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
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Share types decide the "shared" indicator on every row of the note list.
 *
 * Collecting them used to cost one getSharesBy() query per share type per note
 * — eight per note — so listing 500 notes issued 4000 queries. loadShareTypes()
 * replaces that with one getSharesInFolder() call per folder. These tests cover
 * both halves of that claim: the query count really is per-folder, and the
 * values handed to the payload are unchanged.
 */
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
			->willReturnCallback(function (string $userId, Folder $folder): array {
				$this->getSharesInFolderCalls++;
				self::assertSame(self::OWNER, $userId, 'shares must be looked up as the folder owner');
				return $this->sharesInFolder[$folder->getPath()] ?? [];
			});
		$this->shareManager->method('getSharesBy')
			->willReturnCallback(function (string $userId, int $shareType, ?\OCP\Files\Node $node): array {
				$this->getSharesByCalls++;
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
	private function file(int $id): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getOwner')->willReturn($this->user());
		return $file;
	}

	/** @return IUser&MockObject */
	private function user(): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn(self::OWNER);
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

	/**
	 * The scaling claim, stated as a test: adding notes must not add queries,
	 * only adding folders may.
	 */
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

	/**
	 * getSharesBy() was only ever asked about eight specific types, whereas
	 * getSharesInFolder() returns everything every provider knows. Types outside
	 * the list must stay unreported — TYPE_USERGROUP in particular is the
	 * per-user half of a group share and would double-report it.
	 */
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

	/**
	 * A folder with no owner cannot be asked about shares. Caching an empty
	 * result for its notes would silently drop the shared indicator, so the
	 * preload has to disown the whole tree and let each note be looked up.
	 */
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
