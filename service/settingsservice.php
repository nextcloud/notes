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
	private $settings = [
		"notesPath" => "Notes",
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
		foreach($this->settings as $name => $value) {
			$this->settings[$name] = isset($settings[$name]) ? $settings[$name] : $value;
		}

		$this->config->setUserValue($this->uid, 'notes', 'settings', json_encode($this->settings));
	}

	public function get($name = null, $default = null) {
		$settings = json_decode($this->config->getUserValue($this->uid, 'notes', 'settings'));
		if(!$settings || !is_object($settings)) $settings = $this->settings;
		return $name ? (property_exists($settings, $name) ? $settings->{$name} : $default) : $settings;
	}
}
