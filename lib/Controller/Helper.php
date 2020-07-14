<?php

declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\Application;
use OCA\Notes\Service\Util;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IUserSession;

class Helper {

	/** @var ILogger */
	public $logger;
	/** @var string */
	private $appName;
	/** @var IUserSession */
	private $userSession;

	public function __construct(
		IUserSession $userSession,
		ILogger $logger,
		string $appName
	) {
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->appName = $appName;
	}

	public function getUID() : string {
		return $this->userSession->getUser()->getUID();
	}

	public function logException(\Throwable $e) : void {
		$this->logger->logException($e, ['app' => $this->appName]);
	}

	public function createErrorResponse(\Throwable $e, int $statusCode) : JSONResponse {
		$response = [
			'errorType' => get_class($e)
		];
		return new JSONResponse($response, $statusCode);
	}

	public function handleErrorResponse(callable $respond) : JSONResponse {
		try {
			$data = Util::retryIfLocked($respond);
			$response = $data instanceof JSONResponse ? $data : new JSONResponse($data);
		} catch (\OCA\Notes\Service\NoteDoesNotExistException $e) {
			$this->logException($e);
			$response = $this->createErrorResponse($e, Http::STATUS_NOT_FOUND);
		} catch (\OCA\Notes\Service\InsufficientStorageException $e) {
			$this->logException($e);
			$response = $this->createErrorResponse($e, Http::STATUS_INSUFFICIENT_STORAGE);
		} catch (\OCP\Lock\LockedException $e) {
			$this->logException($e);
			$response = $this->createErrorResponse($e, Http::STATUS_LOCKED);
		} catch (\Throwable $e) {
			$this->logException($e);
			$response = $this->createErrorResponse($e, Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		$response->addHeader('X-Notes-API-Versions', implode(', ', Application::$API_VERSIONS));
		return $response;
	}
}
