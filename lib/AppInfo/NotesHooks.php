<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\AppInfo;

use OCA\Notes\Service\MetaService;

use OCP\Files\IRootFolder;
use OCP\Files\Node;

use Psr\Log\LoggerInterface;

class NotesHooks {
	private LoggerInterface $logger;
	private IRootFolder $rootFolder;
	private MetaService $metaService;

	public function __construct(
		LoggerInterface $logger,
		IRootFolder $rootFolder,
		MetaService $metaService,
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
				// $this->logger->debug('preWrite: ' . $node->getPath());
				$this->onFileModified($node);
			}
		);
		$this->listenTo(
			$this->rootFolder,
			'\OC\Files',
			'preTouch',
			function (Node $node) {
				// $this->logger->debug('preTouch: ' . $node->getPath());
				$this->onFileModified($node);
			}
		);
		$this->listenTo(
			$this->rootFolder,
			'\OC\Files',
			'preDelete',
			function (Node $node) {
				// $this->logger->debug('preDelete: ' . $node->getPath());
				$this->onFileModified($node);
			}
		);
		$this->listenTo(
			$this->rootFolder,
			'\OC\Files',
			'preRename',
			function (Node $source, Node $target) {
				// $this->logger->debug('preRename: ' . $source->getPath());
				$this->onFileModified($source);
			}
		);
	}

	private function listenTo($service, string $scope, string $method, callable $callback) : void {
		/* @phan-suppress-next-line PhanUndeclaredMethod */
		$service->listen($scope, $method, $callback);
	}

	private function onFileModified(Node $node) : void {
		// $this->logger->debug('NotesHook for ' . $node->getPath());
		try {
			$this->metaService->deleteByNote($node->getId());
		} catch (\Throwable $e) {
		}
	}
}
