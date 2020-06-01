<?php declare(strict_types=1);

namespace OCA\Notes;

use OCP\AppFramework\App;

class Application extends App {

	public static $API_VERSIONS = [ '0.2', '1.1' ];

	public function __construct(array $urlParams = []) {
		parent::__construct('notes', $urlParams);
	}

	public function register() : void {
		$container = $this->getContainer();
		$container->registerCapability(Capabilities::class);
		$container->query(NotesHooks::class)->register();
	}
}
