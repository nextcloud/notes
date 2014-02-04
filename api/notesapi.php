<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\API;

use \OCA\AppFramework\Controller\Controller;
use \OCA\AppFramework\Core\API;
use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\JSONResponse;
use \OCA\AppFramework\Http\Http;

use \OCA\Notes\Service\NotesService;
use \OCA\Notes\Service\NoteDoesNotExistException;


class NotesAPI extends Controller {

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
	 * @API
	 * @CSRFExemption
	 */
	public function getAll() {
		$hide = explode(',', $this->params('exclude', ''));
		$notes = $this->notesService->getAll();

		// if there are hidden values remove them from the result
		if(count($hide) > 0) {
			foreach ($notes as $note) {
				foreach ($hide as $field) {
					unset($note->$field);
				}
			}
		}

		return new JSONResponse($notes);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 * @API
	 * @CSRFExemption
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
	 * @API
	 * @CSRFExemption
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
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 * @API
	 * @CSRFExemption
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
	 * @API
	 * @CSRFExemption
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


}