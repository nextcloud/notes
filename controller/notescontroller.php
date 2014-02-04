<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Controller;

use \OCA\AppFramework\Controller\Controller;
use \OCA\AppFramework\Core\API;
use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\Response;
use \OCA\AppFramework\Http\JSONResponse;
use \OCA\AppFramework\Http\Http;

use \OCA\Notes\Service\NotesService;
use \OCA\Notes\Service\NoteDoesNotExistException;


class NotesController extends Controller {

	private $notesService;

	public function __construct(API $api, Request $request,
		                        NotesService $notesService){
		parent::__construct($api, $request);
		$this->notesService = $notesService;
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function getAll() {
		$notes = $this->notesService->getAll();
		return new JSONResponse($notes);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function get() {
		$id = (int) $this->params('id');

		// save the last viewed note
		$this->api->setUserValue('notesLastViewedNote', $id);
		try {
			return new JSONResponse($this->notesService->get($id));
		} catch(NoteDoesNotExistException $ex) {
			return new JSONResponse(array(), Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function create() {
		$note = $this->notesService->create();
		return new JSONResponse($note);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
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
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function delete() {
		$id = (int) $this->params('id');
		try {
			$this->notesService->delete($id);
			return new JSONResponse();
		} catch(NoteDoesNotExistException $ex) {
			return new JSONResponse(array(), Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function getConfig() {
		$markdown = $this->api->getUserValue('notesMarkdown') === '1';
		$config = array(
			'markdown' => $markdown
		);
		
		return new JSONResponse($config);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function setConfig() {
		$markdown = $this->api->setUserValue('notesMarkdown', 
			$this->params('markdown'));
		
		return new JSONResponse();
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @CSRFExemption
	 * @IsLoggedInExemption
	 * @Ajax
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