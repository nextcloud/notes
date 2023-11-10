<?php

declare(strict_types=1);

namespace OCA\Notes\AppInfo;

use OCA\Notes\Service\NoteUtil;
use OCA\Notes\Service\SettingsService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;
use OCP\Share\Events\BeforeShareCreatedEvent;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class BeforeShareCreatedListener implements IEventListener {
	private SettingsService $settings;
	private NoteUtil $noteUtil;

	public function __construct(SettingsService $settings, NoteUtil $noteUtil, LoggerInterface $logger) {
		$this->settings = $settings;
		$this->noteUtil = $noteUtil;
		$this->logger = $logger;
	}

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
			$receiverPath = $this->noteUtil->getRoot()->getUserFolder($receiver)->getPath();
			$receiverNotesInternalPath = $this->settings->get($receiver, 'notesPath');
			$receiverNotesPath = $receiverPath . '/' . $receiverNotesInternalPath;
			$this->noteUtil->getOrCreateFolder($receiverNotesPath);

			if ($itemType !== 'file' || strpos($fileSourcePath, $ownerNotesPath) !== 0) {
				return;
			}

			$share->setTarget('/' . $receiverNotesInternalPath . $itemTarget);
		} catch (\Throwable $e) {
			$this->logger->error('Failed to overwrite share target for notes', [
				'exception' => $e,
			]);
		}
	}
}
