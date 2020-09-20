<?php

declare(strict_types=1);

namespace OCA\Notes\AppInfo;

use OCP\Dashboard\IWidget;
use OCP\IL10N;
use OCP\IURLGenerator;

class DashboardWidget implements IWidget {

	/** @var IURLGenerator */
	private $url;
	/** @var IL10N */
	private $l10n;

	public function __construct(
		IURLGenerator $url,
		IL10N $l10n
	) {
		$this->url = $url;
		$this->l10n = $l10n;
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
}
