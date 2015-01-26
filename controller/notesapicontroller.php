<?php
/**
 * ownCloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2012, 2014
 */

namespace OCA\Notes\Controller;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

use OCA\Notes\Service\NotesService;


class NotesApiController extends ApiController {

	use Errors;

	private $service;
	private $settings;
	private $UserId;

	public function __construct($AppName,
	                            IRequest $request,
		                        NotesService $service,
		                        $UserId){
		parent::__construct($AppName, $request);
		$this->service = $service;
		$this->userId = $UserId;
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param string $exclude
	 */
	public function index($exclude='') {
		$hide = explode(',', $exclude);
		$notes = $this->service->getAll($this->userId);

		// if there are hidden values remove them from the result
		if(count($hide) > 0) {
			foreach ($notes as $note) {
				foreach ($hide as $field) {
					if(property_exists($note, $field)) {
						unset($note->$field);
					}
				}
			}
		}

		return new DataResponse($notes);
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @param string $exclude
	 */
	public function get($id, $exclude='') {
		$hide = explode(',', $exclude);

		return $this->respond(function () use ($id, $hide) {
			$note = $this->service->get($id, $this->userId);

			// if there are hidden values remove them from the result
			if(count($hide) > 0) {
				foreach ($hide as $field) {
					if(property_exists($note, $field)) {
						unset($note->$field);
					}
				}
			}
			return $note;
		});
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param string $content
	 */
	public function create($content) {
		return $this->respond(function () use ($content) {
			$note = $this->service->create($this->userId);
			return $this->service->update($note->getId(), $content, $this->userId);
		});
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @param string $content
	 */
	public function update($id, $content) {
		return $this->respond(function () use ($id, $content) {
			return $this->service->update($id, $content, $this->userId);
		});
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 */
	public function destroy($id) {
		return $this->respond(function () use ($id) {
			$this->service->delete($id, $this->userId);
			return [];
		});
	}


}
