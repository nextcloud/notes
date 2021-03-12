<?php

declare(strict_types=1);

namespace OCA\Notes\Service;

use OCA\Notes\AppInfo\Application;

use OCP\IConfig;
use OCP\IL10N;
use OCP\Files\IRootFolder;

class SettingsService {
	private $config;
	private $l10n;
	private $root;

	/* Allowed attributes */
	private $attrs;

	public function __construct(
		IConfig $config,
		IL10N $l10n,
		IRootFolder $root
	) {
		$this->config = $config;
		$this->l10n = $l10n;
		$this->root = $root;
		$this->attrs = [
			'fileSuffix' => [
				'default' => '.txt',
				'validate' => function ($value) {
					if (in_array($value, [ '.txt', '.md' ])) {
						return $value;
					} else {
						return '.txt';
					}
				},
			],
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
		];
	}

	private function getDefaultNotesPath(string $uid) : string {
		$defaultFolder = 'Notes';
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
	public function set(string $uid, array $settings) : void {
		// remove illegal, empty and default settings
		foreach ($settings as $name => $value) {
			if (!array_key_exists($name, $this->attrs)
				|| empty($value)
				|| $value === $this->attrs[$name]['default']
			) {
				unset($settings[$name]);
			} else {
				$settings[$name] = $this->attrs[$name]['validate']($value);
			}
		}
		$this->config->setUserValue($uid, Application::APP_ID, 'settings', json_encode($settings));
	}

	public function getAll(string $uid) : \stdClass {
		$settings = json_decode($this->config->getUserValue($uid, Application::APP_ID, 'settings'));
		if (!is_object($settings)) {
			$settings = new \stdClass();
		}
		// use default for empty settings
		$toBeSaved = false;
		foreach ($this->attrs as $name => $attr) {
			if (!property_exists($settings, $name) || empty($settings->{$name})) {
				$defaultValue = $attr['default'];
				if (is_callable($defaultValue)) {
					$settings->{$name} = $defaultValue($uid);
					$toBeSaved = true;
				} else {
					$settings->{$name} = $defaultValue;
				}
			}
		}
		if ($toBeSaved) {
			$this->set($uid, (array) $settings);
		}
		return $settings;
	}

	/**
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function get(string $uid, string $name) : string {
		$settings = $this->getAll($uid);
		if (property_exists($settings, $name)) {
			return $settings->{$name};
		} else {
			throw new \OCP\PreConditionNotMetException('Setting '.$name.' not found for user '.$uid.'.');
		}
	}
}
