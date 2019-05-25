<?php

namespace OCA\Notes\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;

/**
 * Class PageController
 *
 * @package OCA\Notes\Controller
 */
class PageController extends Controller {

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 */
	public function __construct($AppName, IRequest $request) {
		parent::__construct($AppName, $request);
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function index() {
		$response = new TemplateResponse(
			$this->appName,
			'main',
			[ ]
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedImageDomain('*');
		$response->setContentSecurityPolicy($csp);

		return $response;
	}
}
