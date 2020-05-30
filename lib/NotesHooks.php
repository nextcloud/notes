<?php declare(strict_types=1);

namespace OCA\Notes;

use OCA\Notes\Service\MetaService;

use OCP\ILogger;
use OCP\Files\IRootFolder;
use OCP\Files\Node;

class NotesHooks {
	private $logger;
	private $rootFolder;
	private $metaService;

	public function __construct(
		ILogger $logger,
		IRootFolder $rootFolder,
		MetaService $metaService
	) {
		$this->logger = $logger;
		$this->rootFolder = $rootFolder;
		$this->metaService = $metaService;
	}

	public function register() : void {
		$this->listenTo(
			$this->rootFolder,
			'\OC\Files',
			'preWrite',
			function (Node $node) {
				// $this->logger->warning('preWrite: ' . $node->getPath(), ['app'=>'notes']);
				$this->onFileModified($node);
			}
		);
		$this->listenTo(
			$this->rootFolder,
			'\OC\Files',
			'preTouch',
			function (Node $node) {
				// $this->logger->warning('preTouch: ' . $node->getPath(), ['app'=>'notes']);
				$this->onFileModified($node);
			}
		);
		$this->listenTo(
			$this->rootFolder,
			'\OC\Files',
			'preDelete',
			function (Node $node) {
				// $this->logger->warning('preDelete: ' . $node->getPath(), ['app'=>'notes']);
				$this->onFileModified($node);
			}
		);
		$this->listenTo(
			$this->rootFolder,
			'\OC\Files',
			'preRename',
			function (Node $source, Node $target) {
				// $this->logger->warning('preRename: ' . $source->getPath(), ['app'=>'notes']);
				$this->onFileModified($source);
			}
		);
	}

	private function listenTo($service, string $scope, string $method, callable $callback) : void {
		/* @phan-suppress-next-line PhanUndeclaredMethod */
		$service->listen($scope, $method, $callback);
	}

	private function onFileModified(Node $node) : void {
		try {
			$this->metaService->deleteByNote($node->getId());
		} catch (\Throwable $e) {
		}
	}
}
