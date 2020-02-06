<?php

namespace OCA\Notes\Service;

use OCP\IConfig;

class SettingsService {

	private $config;
	private $root;

	/* Default values */
	private $defaults = [
		'notesPath' => 'Notes',
		'fileSuffix' => '.txt',
	];

	public function __construct(
		IConfig $config
	) {
		$this->config = $config;
	}

	/**
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function set($uid, $settings) {
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

	public function getAll($uid) {
		$settings = json_decode($this->config->getUserValue($uid, 'notes', 'settings'));
		if (is_object($settings)) {
			// use default for empty settings
			foreach ($this->defaults as $name => $defaultValue) {
				if (!property_exists($settings, $name) || empty($settings->{$name})) {
					$settings->{$name} = $defaultValue;
				}
			}
		} else {
			$settings = (object)$this->defaults;
		}
		return $settings;
	}

	/**
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function get($uid, $name) {
		$settings = $this->getAll($uid);
		if (property_exists($settings, $name)) {
			return $settings->{$name};
		} else {
			throw new \OCP\PreConditionNotMetException('Setting '.$name.' not found for user '.$uid.'.');
		}
	}
}
