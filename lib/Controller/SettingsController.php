<?php

declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\Service\SettingsService;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsController extends Controller {
	private SettingsService $service;
	private IUserSession $userSession;

	public function __construct(
		string $appName,
		IRequest $request,
		SettingsService $service,
		IUserSession $userSession
	) {
		parent::__construct($appName, $request);
		$this->service = $service;
		$this->userSession = $userSession;
	}

	private function getUID(): string {
		return $this->userSession->getUser()->getUID();
	}

	/**
	 * @NoAdminRequired
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function set(): JSONResponse {
		$this->service->set(
			$this->getUID(),
			$this->request->getParams()
		);
		return $this->get();
	}

	/**
	 * @NoAdminRequired
	 */
	public function get(): JSONResponse {
		return new JSONResponse($this->service->getAll($this->getUID()));
	}

	/**
	 * @NoAdminRequired
	 */
	public function migrate(): JSONResponse {
		$this->service->delete($this->getUID(), 'editorHint');
		return new JSONResponse();
	}
}
