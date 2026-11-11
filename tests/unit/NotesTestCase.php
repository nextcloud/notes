<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit;

use OCA\Notes\Service\NoteUtil;
use OCA\Notes\Service\SettingsService;
use OCA\Notes\Service\TagService;
use OCA\Notes\Service\Util;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Share\IManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class NotesTestCase extends TestCase {
	protected function createNoteUtil(?IManager $shareManager = null): NoteUtil {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$db = $this->createMock(IDBConnection::class);
		$db->method('supports4ByteText')->willReturn(true);

		return new NoteUtil(
			new Util($l10n, $this->createMock(LoggerInterface::class)),
			$this->createMock(IRootFolder::class),
			$db,
			$this->createMock(TagService::class),
			$shareManager ?? $this->createMock(IManager::class),
			$this->createMock(IUserSession::class),
			$this->createMock(SettingsService::class),
		);
	}
}
