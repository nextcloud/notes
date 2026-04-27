<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Listener;

use OCA\Notes\Service\MetaService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\BeforeNodeTouchedEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\Node;

/** @template-implements IEventListener<BeforeNodeWrittenEvent|BeforeNodeTouchedEvent|BeforeNodeDeletedEvent|BeforeNodeRenamedEvent|Event> */
class NoteFileEventsListener implements IEventListener {
	public function __construct(
		private MetaService $metaService,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforeNodeWrittenEvent) {
			$this->onFileModified($event->getNode());
		} elseif ($event instanceof BeforeNodeTouchedEvent) {
			$this->onFileModified($event->getNode());
		} elseif ($event instanceof BeforeNodeDeletedEvent) {
			$this->onFileModified($event->getNode());
		} elseif ($event instanceof BeforeNodeRenamedEvent) {
			$this->onFileModified($event->getSource());
		}
	}

	private function onFileModified(Node $node): void {
		try {
			$this->metaService->deleteByNote($node->getId());
		} catch (\Throwable $e) {
			// Intentionally non-fatal: MetaService::getAll() will reconcile on next sync.
			// Consider: Log at debug level so persistent failures remain diagnosable.
			// (logger would need to be injected if added)
		}
	}
}
