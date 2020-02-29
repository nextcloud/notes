<?php

namespace OCA\Notes;

use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {

	public function getCapabilities() {
		return [
			'notes' => [
				'api_version' => [ '0.2' ],
			],
		];
	}
}
