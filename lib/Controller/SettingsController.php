<?php
namespace OCA\Notes\Controller;

use OCP\AppFramework\Controller;

use OCP\IRequest;
use OCP\IUserSession;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Notes\Service\SettingsService;

class SettingsController extends Controller {

	private $service;
	private $userSession;

	public function __construct(
		$appName,
		IRequest $request,
		SettingsService $service,
		IUserSession $userSession
	) {
		parent::__construct($appName, $request);
		$this->service = $service;
		$this->userSession = $userSession;
	}

	private function getUID() {
		return $this->userSession->getUser()->getUID();
	}

	/**
	 * @NoAdminRequired
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function set() {
		$this->service->set(
			$this->getUID(),
			$this->request->getParams()
		);
		return $this->get();
	}

	/**
	 * @NoAdminRequired
	 */
	public function get() {
		return new JSONResponse($this->service->getAll($this->getUID()));
	}
}
