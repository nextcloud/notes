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
			'fileSuffix' => $this->getListAttrs('.txt', '.md', 'custom'),
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
			'noteMode' => $this->getListAttrs('edit', 'preview'),
			'customSuffix' => [
				'default' => '.txt',
				'validate' => function ($value) {
					$out = ltrim(preg_replace('/[^A-Za-z0-9.-]/', '', $value), '.');
					if (empty($out)) {
						$out = 'txt';
					}
					return '.' . $out;
				},
			],
		];
	}

	private function getListAttrs(...$values) : array {
		$first = $values[0];
		return [
			'default' => $first,
			'validate' => function ($value) use ($values, $first) {
				if (in_array($value, $values)) {
					return $value;
				} else {
					return $first;
				}
			},
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
			if (!array_key_exists($name, $this->attrs)
				|| $value === null
				|| $value === $this->attrs[$name]['default']
			) {
				unset($settings[$name]);
			}
		}
		$this->config->setUserValue($uid, Application::APP_ID, 'settings', json_encode($settings));
	}

	private function getSettingsFromDB(string $uid) : \stdClass {
		$settings = json_decode($this->config->getUserValue($uid, Application::APP_ID, 'settings'));
		if (!is_object($settings)) {
			$settings = new \stdClass();
		}
		return $settings;
	}

	public function getAll(string $uid) : \stdClass {
		$settings = $this->getSettingsFromDB($uid);
		// use default for empty settings
		$toBeSaved = false;
		foreach ($this->attrs as $name => $attr) {
			if (!property_exists($settings, $name)) {
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
