<?php

declare(strict_types=1);

namespace OCA\Notes\AppInfo;

use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {
	private IAppManager $appManager;

	public function __construct(IAppManager $appManager) {
		$this->appManager = $appManager;
	}

	public function getCapabilities(): array {
		return [
			Application::APP_ID => [
				'api_version' => Application::$API_VERSIONS,
				'version' => $this->appManager->getAppVersion(Application::APP_ID),
			],
		];
	}
}
