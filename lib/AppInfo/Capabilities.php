<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\AppInfo;

use OCA\Notes\Service\NoteUtil;
use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {
	public function __construct(
		private IAppManager $appManager,
		private NoteUtil $noteUtil,
		private ?string $userId,
	) {
	}

	public function getCapabilities(): array {
		return [
			Application::APP_ID => [
				'api_version' => Application::$API_VERSIONS,
				'version' => $this->appManager->getAppVersion(Application::APP_ID),
				'notes_path' => $this->userId !== null && $this->userId !== ' ' ? $this->noteUtil->getNotesFolderUserPath($this->userId, true) : null,
			],
		];
	}
}
