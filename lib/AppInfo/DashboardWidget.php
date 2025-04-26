<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\AppInfo;

use OCA\Notes\Service\Note;
use OCA\Notes\Service\NotesService;
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\IL10N;
use OCP\IURLGenerator;

class DashboardWidget implements IWidget, IButtonWidget, IAPIWidget, IIconWidget {
	private IURLGenerator $url;
	private IL10N $l10n;
	private NotesService $notesService;

	public function __construct(
		IURLGenerator $url,
		IL10N $l10n,
		NotesService $notesService,
	) {
		$this->url = $url;
		$this->l10n = $l10n;
		$this->notesService = $notesService;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'notes';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Notes');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 30;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconClass(): string {
		return 'icon-notes';
	}

	/**
	 * @inheritDoc
	 */
	public function getUrl(): ?string {
		return $this->url->linkToRouteAbsolute('notes.page.index');
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		\OCP\Util::addScript('notes', 'notes-dashboard');
	}

	public function getWidgetButtons(string $userId): array {
		$buttons = [
			new WidgetButton(
				WidgetButton::TYPE_NEW,
				$this->url->linkToRouteAbsolute('notes.page.create'),
				$this->l10n->t('Create new note')
			)
		];
		if ($this->notesService->countNotes($userId) > 7) {
			$buttons[] = new WidgetButton(
				WidgetButton::TYPE_MORE,
				$this->url->linkToRouteAbsolute('notes.page.index'),
				$this->l10n->t('More notes')
			);
		}
		return $buttons;
	}

	public function getItems(string $userId, ?string $since = null, int $limit = 7): array {
		$notes = $this->notesService->getTopNotes($userId);
		$notes = array_slice($notes, 0, $limit);
		return array_values(array_map(function (Note $note): WidgetItem {
			$excerpt = '';
			try {
				$excerpt = $note->getExcerpt();
			} catch (\Throwable $e) {
			}
			$link = $this->url->linkToRouteAbsolute('notes.page.indexnote', ['id' => $note->getId()]);
			$icon = $note->getFavorite()
				? $this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/starred.svg'))
				: $this->getIconUrl();
			return new WidgetItem($note->getTitle(), $excerpt, $link, $icon, (string)$note->getModified());
		}, $notes));
	}

	public function getIconUrl(): string {
		return $this->url->getAbsoluteURL($this->url->imagePath('notes', 'notes-dark.svg'));
	}
}
