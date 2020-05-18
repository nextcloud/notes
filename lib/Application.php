<?php declare(strict_types=1);

namespace OCA\Notes;

use OCP\AppFramework\App;

class Application extends App {

	public static $API_VERSIONS = [ '0.2', '1.0' ];

	public function __construct(array $urlParams = []) {
		parent::__construct('notes', $urlParams);
	}

	public function register() : void {
		$container = $this->getContainer();
		$container->registerCapability(Capabilities::class);
		$server = $container->getServer();
		$server->getNavigationManager()->add(function () use ($server) {
			$urlGenerator = $server->getURLGenerator();
			$l10n = $server->getL10N('notes');
			return [
				'id' => 'notes',
				'order' => 10,
				'href' => $urlGenerator->linkToRoute('notes.page.index'),
				'icon' => $urlGenerator->imagePath('notes', 'notes.svg'),
				'name' => $l10n->t('Notes'),
			];
		});
	}
}
