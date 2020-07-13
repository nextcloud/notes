<?php

declare(strict_types=1);

namespace OCA\Notes\Service;

use OCP\IConfig;
use OCP\IL10N;
use OCP\Files\IRootFolder;

class SettingsService {
	private $config;
	private $l10n;
	private $root;

	/* Default values */
	private $defaults;

	public function __construct(
		IConfig $config,
		IL10N $l10n,
		IRootFolder $root
	) {
		$this->config = $config;
		$this->l10n = $l10n;
		$this->root = $root;
		$this->defaults = [
			'fileSuffix' => '.txt',
			'notesPath' => function (string $uid) {
				return $this->getDefaultNotesPath($uid);
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
		// remove illegal, empty and default settings
		foreach ($settings as $name => $value) {
			if (!array_key_exists($name, $this->defaults)
				|| empty($value)
				|| $value === $this->defaults[$name]
			) {
				unset($settings[$name]);
			}
		}
		$this->config->setUserValue($uid, 'notes', 'settings', json_encode($settings));
	}

	public function getAll(string $uid) : \stdClass {
		$settings = json_decode($this->config->getUserValue($uid, 'notes', 'settings'));
		if (!is_object($settings)) {
			$settings = new \stdClass();
		}
		// use default for empty settings
		$toBeSaved = false;
		foreach ($this->defaults as $name => $defaultValue) {
			if (!property_exists($settings, $name) || empty($settings->{$name})) {
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
