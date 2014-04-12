<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Controller;

use \OCP\AppFramework\Controller;
use \OCP\IRequest;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http;

use \OCA\Notes\Core\API;
use \OCA\Notes\Service\NotesService;
use \OCA\Notes\Service\NoteDoesNotExistException;


class NotesController extends Controller {

	private $notesService;
	private $api;

	public function __construct(API $api, IRequest $request,
		                        NotesService $notesService){
		parent::__construct($api->getAppName(), $request);
		$this->notesService = $notesService;
		$this->api = $api;
	}


	/**
	 * @NoAdminRequired
	 */
	public function index() {
		$notes = $this->notesService->getAll();
		return new JSONResponse($notes);
	}


	/**
	 * @NoAdminRequired
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
	 * @NoAdminRequired
	 */
	public function create() {
		$note = $this->notesService->create();
		return new JSONResponse($note);
	}


	/**
	 * @NoAdminRequired
	 */
	public function update() {
		$id = (int) $this->params('id');
		$content = $this->params('content');

		var_dump($this->request);

		echo $content;
		die();
		try {
			return new JSONResponse($this->notesService->update($id, $content));
		} catch(NoteDoesNotExistException $ex) {
			return new JSONResponse(array(), Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @NoAdminRequired
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
	 */
	public function getConfig() {
		$markdown = $this->api->getUserValue('notesMarkdown') === '1';
		$config = array(
			'markdown' => $markdown
		);
		
		return new JSONResponse($config);
	}


	/**
	 * @NoAdminRequired
	 */
	public function setConfig() {
		$markdown = $this->api->setUserValue('notesMarkdown', 
			$this->params('markdown'));
		
		return new JSONResponse();
	}


}