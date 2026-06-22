final <?php

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

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getId(): string {
		return 'notes';
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getTitle(): string {
		return $this->l10n->t('Notes');
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getOrder(): int {
		return 30;
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getIconClass(): string {
		return 'icon-notes';
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getUrl(): ?string {
		return $this->url->linkToRouteAbsolute('notes.page.index');
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function load(): void {
		\OCP\Util::addScript('notes', 'notes-dashboard');
	}

	#[\Override]
	public function getWidgetButtons(string $userId): array {
		$buttons = [
			new WidgetButton(
				WidgetButton::TYPE_NEW,
				$this->url->linkToRouteAbsolute('notes.page.createGet'),
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

	#[\Override]
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

	#[\Override]
	public function getIconUrl(): string {
		return $this->url->getAbsoluteURL($this->url->imagePath('notes', 'notes-dark.svg'));
	}
}
