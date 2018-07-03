<?php

namespace OCA\Notes\Service;
use OCP\AppFramework\Controller;

use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Http\JSONResponse;

class SettingsService
{
	private $config;
	private $uid;
	private $root;

	/* Default values */
	private $defaults = [
		"notesPath" => "Notes",
		"fileSuffix" => ".txt",
	];

	public function __construct(
		IConfig $config,
		IUserSession $userSession
	) {
		$this->config = $config;
		$this->uid = $userSession->getUser()->getUID();
	}

	/**
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function set($settings) {
		// remove illegal, empty and default settings
		foreach($settings as $name => $value) {
			if(!array_key_exists($name, $this->defaults)
				|| empty($value)
				|| $value === $this->defaults[$name]
			) {
				unset($settings[$name]);
			}
		}
		$this->config->setUserValue($this->uid, 'notes', 'settings', json_encode($settings));
	}

	public function getAll() {
		$settings = json_decode($this->config->getUserValue($this->uid, 'notes', 'settings'));
		if(is_object($settings)) {
			// use default for empty settings
			foreach($this->defaults as $name => $defaultValue) {
				if(!property_exists($settings, $name) || empty($settings->{$name})) {
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
	public function get($name) {
		$settings = $this->getAll();
		if(property_exists($settings, $name)) {
			return $settings->{$name};
		} else {
			throw new \OCP\PreConditionNotMetException('Setting '.$name.' not found.');
		}
	}
}
