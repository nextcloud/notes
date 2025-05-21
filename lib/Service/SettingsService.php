<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Service;

use OCA\Notes\AppInfo\Application;

use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;

class SettingsService {
	private IConfig $config;
	private IL10N $l10n;
	private IRootFolder $root;
	private IAppManager $appManager;

	/* Allowed attributes */
	private array $attrs;

	private $defaultSuffixes = [ '.md', '.txt' ];

	public function __construct(
		IConfig $config,
		IL10N $l10n,
		IRootFolder $root,
		IAppManager $appManager,
	) {
		$this->config = $config;
		$this->l10n = $l10n;
		$this->root = $root;
		$this->appManager = $appManager;
		$this->attrs = [
			'fileSuffix' => $this->getListAttrs('fileSuffix', [...$this->defaultSuffixes, 'custom']),
			'notesPath' => [
				'default' => function (string $uid) {
					return $this->getDefaultNotesPath($uid);
				},
				'validate' => function ($value) {
					$value = str_replace([ '/', '\\' ], DIRECTORY_SEPARATOR, $value);
					$parts = explode(DIRECTORY_SEPARATOR, $value);
					$path = [];
					foreach ($parts as $part) {
						if ($part === '..') {
							array_pop($path);
						} elseif (strlen($part) && $part !== '.') {
							array_push($path, $part);
						}
					}
					return implode(DIRECTORY_SEPARATOR, $path);
				},
			],
			'noteMode' => $this->getListAttrs('noteMode', $this->getAvailableEditorModes()),
			'customSuffix' => [
				'default' => $this->defaultSuffixes[0],
				'validate' => function ($value) {
					$out = ltrim(preg_replace('/[^A-Za-z0-9.-]/', '', $value), '.');
					if (empty($out)) {
						$out = substr($this->defaultSuffixes[0], 1);
					}
					return '.' . $out;
				},
			],
		];
	}

	private function getListAttrs(string $attributeName, array $values) : array {
		$default = $this->config->getAppValue(Application::APP_ID, $attributeName, $values[0]);

		return [
			'default' => $default,
			'validate' => function ($value) use ($values, $default) {
				if (in_array($value, $values)) {
					return $value;
				} else {
					return $default;
				}
			},
		];
	}

	public function getDefaultNotesPath(string $uid) : string {
		$defaultFolder = $this->config->getAppValue(Application::APP_ID, 'defaultFolder', 'Notes');
		$defaultExists = $this->root->getUserFolder($uid)->nodeExists($defaultFolder);
		if ($defaultExists) {
			return $defaultFolder;
		} else {
			return $this->l10n->t($defaultFolder);
		}
	}

	/**
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function set(string $uid, array $settings, bool $writeDefaults = false) : void {
		// load existing values for missing attributes
		$oldSettings = $this->getSettingsFromDB($uid);
		foreach ($oldSettings as $name => $value) {
			if (!array_key_exists($name, $settings)) {
				$settings[$name] = $value;
			}
		}
		// remove illegal, empty and default settings
		foreach ($settings as $name => $value) {
			if ($value !== null && array_key_exists($name, $this->attrs)) {
				$settings[$name] = $value = $this->attrs[$name]['validate']($value);
			}
			if ($name === 'notesPath' && $value !== null) {
				continue;
			}
			$default = is_callable($this->attrs[$name]['default']) ? $this->attrs[$name]['default']($uid) : $this->attrs[$name]['default'];
			if (!$writeDefaults && (!array_key_exists($name, $this->attrs)
				|| $value === null
				|| $value === $default
			)) {
				unset($settings[$name]);
			}
		}
		$this->config->setUserValue($uid, Application::APP_ID, 'settings', json_encode($settings));
	}

	/**
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function setPublic(string $uid, array $settings) : void {
		if (array_key_exists('fileSuffix', $settings)
			&& $settings['fileSuffix'] !== null
			&& !in_array($settings['fileSuffix'], $this->defaultSuffixes)
		) {
			$settings['customSuffix'] = $settings['fileSuffix'];
			$settings['fileSuffix'] = 'custom';
		}
		$this->set($uid, $settings);
	}

	private function getSettingsFromDB(string $uid) : \stdClass {
		$settings = json_decode($this->config->getUserValue($uid, Application::APP_ID, 'settings'));
		if (!is_object($settings)) {
			$settings = new \stdClass();
		}
		return $settings;
	}

	public function getAll(string $uid, $saveInitial = false) : \stdClass {
		$settings = $this->getSettingsFromDB($uid);
		// use default for empty settings
		$toBeSaved = false;
		foreach ($this->attrs as $name => $attr) {
			if (!property_exists($settings, $name)) {
				$defaultValue = $attr['default'];
				if (is_callable($defaultValue)) {
					$settings->{$name} = $defaultValue($uid);
					$toBeSaved = $saveInitial;
				} else {
					$settings->{$name} = $defaultValue;
				}
			}
		}
		if ($toBeSaved) {
			$this->set($uid, (array)$settings);
		}
		return $settings;
	}

	/**
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function get(string $uid, string $name, bool $saveInitial = false) : string {
		$settings = $this->getAll($uid, $saveInitial);
		if (property_exists($settings, $name)) {
			return $settings->{$name};
		} else {
			throw new \OCP\PreConditionNotMetException('Setting ' . $name . ' not found for user ' . $uid . '.');
		}
	}

	public function delete(string $uid, string $name): void {
		$this->config->deleteUserValue($uid, Application::APP_ID, $name);
	}

	public function getPublic(string $uid) : \stdClass {
		// initialize and load settings
		$settings = $this->getAll($uid, true);
		// translate internal settings to public settings
		if ($settings->fileSuffix === 'custom') {
			$settings->fileSuffix = $settings->customSuffix;
		}
		unset($settings->customSuffix);
		return $settings;
	}

	private function getAvailableEditorModes(): array {
		return \OCP\Util::getVersion()[0] >= 26 && $this->appManager->isEnabledForUser('text')
			? ['rich', 'edit', 'preview']
			: ['edit', 'preview'];
	}
}
