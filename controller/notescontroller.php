<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Controller;

use \OCA\AppFramework\Controller\Controller;
use \OCA\AppFramework\Core\API;
use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\JSONResponse;

use \OCA\Notes\Service\NotesService;


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
		$note = $this->notesService->get($id);
		return new JSONResponse($note);
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
		$title = $this->params('title');
		$content = $this->params('content');
		$note = $this->notesService->update($id, $title, $content);
		return new JSONResponse($note);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function delete() {
		$id = (int) $this->params('id');
		$this->notesService->delete($id);
		return new JSONResponse();	
	}


}