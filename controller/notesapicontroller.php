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

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\Response;
use \OCP\AppFramework\Http;
use \OCP\IRequest;

use \OCA\Notes\Service\NotesService;
use \OCA\Notes\Service\NoteDoesNotExistException;


class NotesApiController extends Controller {

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
	 * @API
	 * @NoCSRFRequired
	 */
	public function index() {
		$hide = explode(',', $this->params('exclude', ''));
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
	 * @API
	 * @NoCSRFRequired
	 */
	public function get() {
		$id = (int) $this->params('id');
		$hide = explode(',', $this->params('exclude', ''));

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
	 * @API
	 * @NoCSRFRequired
	 */
	public function create() {
		$content = $this->params('content');
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
	 * @API
	 * @NoCSRFRequired
	 */
	public function update() {
		$id = (int) $this->params('id');
		$content = $this->params('content');
		try {
			return new JSONResponse($this->notesService->update($id, $content));
		} catch(NoteDoesNotExistException $ex) {
			return new JSONResponse(array(), Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @NoAdminRequired
	 * @API
	 * @NoCSRFRequired
	 */
	public function destroy() {
		$id = (int) $this->params('id');
		try {
			$this->notesService->delete($id);
			return new JSONResponse();
		} catch(NoteDoesNotExistException $ex) {
			return new JSONResponse(array(), Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function cors() {
		// needed for webapps access due to cross origin request policy
		if(isset($this->request->server['HTTP_ORIGIN'])) {
			$origin = $this->request->server['HTTP_ORIGIN'];
		} else {
			$origin = '*';
		}

		$response = new Response();
		$response->addHeader('Access-Control-Allow-Origin', $origin);
		$response->addHeader('Access-Control-Allow-Methods', 
			'PUT, POST, GET, DELETE');
		$response->addHeader('Access-Control-Allow-Credentials', 'true');
		$response->addHeader('Access-Control-Max-Age', '1728000');
		$response->addHeader('Access-Control-Allow-Headers', 
			'Authorization, Content-Type');
		return $response;
	}

}
