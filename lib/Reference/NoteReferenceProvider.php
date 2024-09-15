<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Reference;

use OCA\Notes\Service\NoteDoesNotExistException;
use OCA\Notes\Service\NotesService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\Reference;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;

class NoteReferenceProvider extends ADiscoverableReferenceProvider {
	private const RICH_OBJECT_TYPE = 'notes_note';
	private ?string $userId;
	private IL10N $l10n;
	private LoggerInterface $logger;

	public function __construct(
		private IURLGenerator $urlGenerator,
		private NotesService $notesService,
		IUserSession $userSession,
		IFactory $l10n,
		LoggerInterface $logger,
	) {
		$this->userId = $userSession->getUser()?->getUID();
		$this->l10n = $l10n->get('notes');
		$this->logger = $logger;
	}

	public function matchReference(string $referenceText): bool {
		return $this->getNoteLinkId($referenceText) !== null;
	}

	private function getNoteLinkId(string $referenceText): ?int {
		$start = $this->urlGenerator->getAbsoluteURL('/apps/notes/note/');
		$startIndex = $this->urlGenerator->getAbsoluteURL('/index.php/apps/notes/note/');

		foreach ([$start, $startIndex] as $url) {
			preg_match('/^' . preg_quote($url, '/') . '([0-9]+)$/', $referenceText, $matches);
			if ($matches && count($matches) > 1) {
				return (int)$matches[1];
			}
		}

		return null;
	}

	public function resolveReference(string $referenceText): ?IReference {
		$noteId = $this->getNoteLinkId($referenceText);
		$reference = new Reference($referenceText);

		if ($this->userId !== null && $noteId !== null) {
			try {
				$note = $this->notesService->get($this->userId, $noteId);
			} catch (NoteDoesNotExistException) {
				$this->logger->warning('Could not find a note with id: ' . $noteId);
				return null;
			}
			$reference->setTitle($note->getTitle());
			$reference->setDescription($note->getCategory());
			$reference->setImageUrl($this->urlGenerator->linkToRouteAbsolute('core.Preview.getPreviewByFileId', ['x' => 600, 'y' => 300, 'fileId' => $note->getId()]));

			return $reference;
		}

		return null;
	}

	public function getCachePrefix(string $referenceId): string {
		return $referenceId;
	}

	public function getCacheKey(string $referenceId): string {
		return $this->userId ?? '';
	}

	public function getId(): string {
		return 'notes' ;
	}

	public function getTitle(): string {
		return $this->l10n->t('Notes');
	}

	public function getOrder(): int {
		return 10;
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->imagePath('notes', 'notes.svg');
	}
}
