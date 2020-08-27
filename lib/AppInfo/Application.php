<?php

declare(strict_types=1);

namespace OCA\Notes\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public static $API_VERSIONS = [ '0.2', '1.1' ];

	public function __construct(array $urlParams = []) {
		parent::__construct('notes', $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
	}

	public function boot(IBootContext $context): void {
		$context->getAppContainer()->get(NotesHooks::class)->register();
	}
}
