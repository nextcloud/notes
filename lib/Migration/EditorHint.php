<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Notes\Migration;

use OCA\Notes\AppInfo\Application;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class EditorHint implements IRepairStep {
	private IConfig $config;
	private IUserManager $userManager;
	public function __construct(IConfig $config, IUserManager $userManager) {
		$this->config = $config;
		$this->userManager = $userManager;
	}

	public function getName() {
		return 'Show a hint about the new editor to existing users';
	}

	public function run(IOutput $output) {
		$appVersion = $this->config->getAppValue('notes', 'installed_version');

		if (!$appVersion || version_compare($appVersion, '4.7.0') !== -1) {
			return;
		}

		$this->userManager->callForSeenUsers(function (IUser $user) {
			if ($this->config->getUserValue($user->getUID(), Application::APP_ID, 'notesLastViewedNote', '') === '') {
				return;
			}

			$this->config->setUserValue($user->getUID(), Application::APP_ID, 'editorHint', 'yes');
		});
	}
}
