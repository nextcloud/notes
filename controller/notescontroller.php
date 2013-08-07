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

use \OCA\Notes\BusinessLayer\NotesBusinessLayer;


class NotesController extends Controller {

	private $notesBusinessLayer;

	public function __construct(API $api, Request $request, 
		                        NotesBusinessLayer $notesBusinessLayer){
		parent::__construct($api, $request);
		$this->notesBusinessLayer = $notesBusinessLayer;
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function getAll() {
		$notes = $this->notesBusinessLayer->getAll();
		return new JSONResponse($notes);	
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function get() {
		$id = (int) $this->params('id');
		$note = $this->notesBusinessLayer->get($id);
		return new JSONResponse($note);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function create() {
		$note = $this->notesBusinessLayer->create();
		return new JSONResponse($note);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function update() {
		$id = $this->params('id');
		$content = $this->params('content');
		$note = $this->notesBusinessLayer->update($id, $content);
		return new JSONResponse($note);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function delete() {
		$id = (int) $this->params('id');
		$this->notesBusinessLayer->delete($id);
		return new JSONResponse();	
	}


}