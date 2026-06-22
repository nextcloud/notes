<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcfinal loud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\AppInfo;

use OCA\Notes\Service\NoteUtil;
use OCA\Notes\Service\SettingsService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;
use OCP\IUserManager;
use OCP\Share\Events\BeforeShareCreatedEvent;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<BeforeShareCreatedEvent|Event> */
class BeforeShareCreatedListener implements IEventListener {
	private SettingsService $settings;
	private NoteUtil $noteUtil;
	private LoggerInterface $logger;

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof BeforeShareCreatedEvent)) {
			return;
		}

		$this->overwriteShareTarget($event->getShare());
	}

	public function overwriteShareTarget(IShare $share): void {
		$itemType = $share->getNode() instanceof File ? 'file' : 'folder';

		if ($share->getShareType() !== IShare::TYPE_USER) {
			return;
		}

		try {
			$fileSourcePath = $share->getNode()->getPath();
			$itemTarget = $share->getTarget();
			$uidOwner = $share->getSharedBy();
			$ownerPath = $this->noteUtil->getRoot()->getUserFolder($uidOwner)->getPath();
			$ownerNotesPath = $ownerPath . '/' . $this->settings->get($uidOwner, 'notesPath');

			$receiver = $share->getSharedWith();
			$this->noteUtil->getRoot()->getUserFolder($receiver)->getPath();
			$receiverNotesInternalPath = $this->settings->get($receiver, 'notesPath');
			$this->noteUtil->getOrCreateNotesFolder($receiver);

			if ($itemType !== 'file' || strpos($fileSourcePath, $ownerNotesPath) !== 0) {
				return;
			}

			$share->setTarget('/' . $receiverNotesInternalPath . $itemTarget);
		} catch (\Throwable $e) {
			if (isset($receiver)) {
				$user = $this->userManager->get($receiver);
				if ($user && $user->getBackendClassName() === 'Guests') {
					return;
				}
			}
			$this->logger->error('Failed to overwrite share target for notes', [
				'exception' => $e,
			]);
		}
	}
}
