<?php declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\Application;
use OCA\Notes\Service\InsufficientStorageException;
use OCA\Notes\Service\NoteDoesNotExistException;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;

class Helper {

	public $logger;
	private $appName;

	public function __construct(
		ILogger $logger,
		string $appName
	) {
		$this->logger = $logger;
		$this->appName = $appName;
	}

	public function handleErrorResponse(callable $respond) : JSONResponse {
		try {
			// retry on LockedException
			$maxRetries = 5;
			for ($try=1; $try <= $maxRetries; $try++) {
				try {
					$data = $respond();
					break;
				} catch (\OCP\Lock\LockedException $e) {
					if ($try >= $maxRetries) {
						throw $e;
					}
					sleep(1);
				}
			}
			$response = $data instanceof JSONResponse ? $data : new JSONResponse($data);
		} catch (NoteDoesNotExistException $e) {
			$this->logger->logException($e, [ 'app' => $this->appName ]);
			$response = new JSONResponse([], Http::STATUS_NOT_FOUND);
		} catch (InsufficientStorageException $e) {
			$this->logger->logException($e, [ 'app' => $this->appName ]);
			$response = new JSONResponse([], Http::STATUS_INSUFFICIENT_STORAGE);
		} catch (\OCP\Lock\LockedException $e) {
			$this->logger->logException($e, [ 'app' => $this->appName ]);
			$response = new JSONResponse([], Http::STATUS_LOCKED);
		} catch (\Throwable $e) {
			$this->logger->logException($e, [ 'app' => $this->appName ]);
			$response = new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		$response->addHeader('X-Notes-API-Versions', implode(', ', Application::$API_VERSIONS));
		return $response;
	}
}
