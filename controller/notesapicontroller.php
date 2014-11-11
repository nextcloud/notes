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

use \OCP\AppFramework\ApiController;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\Response;
use \OCP\AppFramework\Http;
use \OCP\IRequest;

use \OCA\Notes\Service\NotesService;
use \OCA\Notes\Service\NoteDoesNotExistException;


class NotesApiController extends ApiController {

	private $notesService;
	private $settings;

	public function __construct($appName,
	                            IRequest $request,
		                        NotesService $notesService){
		parent::__construct($appName, $request);
		$this->notesService = $notesService;
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
		$notes = $this->notesService->getAll();

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

		return new JSONResponse($notes);
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

		try {
			$note = $this->notesService->get($id);

			// if there are hidden values remove them from the result
			if(count($hide) > 0) {
				foreach ($hide as $field) {
					if(property_exists($note, $field)) {
						unset($note->$field);
					}
				}
			}
			return new JSONResponse($note);

		} catch(NoteDoesNotExistException $ex) {
			return new JSONResponse(array(), Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param string $content
	 */
	public function create($content) {
		$note = $this->notesService->create();

		try {
			$note = $this->notesService->update($note->getId(), $content);
			return new JSONResponse($note);
		} catch(NoteDoesNotExistException $ex) {
			return new JSONResponse(array(), Http::STATUS_NOT_FOUND);
		}
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
		try {
			return new JSONResponse($this->notesService->update($id, $content));
		} catch(NoteDoesNotExistException $ex) {
			return new JSONResponse(array(), Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 */
	public function destroy($id) {
		try {
			$this->notesService->delete($id);
			return new JSONResponse();
		} catch(NoteDoesNotExistException $ex) {
			return new JSONResponse(array(), Http::STATUS_NOT_FOUND);
		}
	}


}
