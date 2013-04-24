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

use \OCA\Notes\BusinessLayer\NotesBusinessLayer;


class NotesController extends Controller {

	private $businessLayer;

	public function __construct(API $api, Request $request, 
		                        NotesBusinessLayer $notesBizLayer){
		parent::__construct($api, $request);
		$this->businessLayer = $notesBizLayer;
	}


	/**
	 * ATTENTION!!!
	 * The following comments are needed "here" but turn off security checks
	 * Please look up their meaning in the documentation:
	 * http://doc.owncloud.org/server/master/developer_manual/app/appframework/controllers.html
	 *
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function getAll() {
		$notes = $this->businessLayer->getAllNotes();
		$params = array(
			'notes' => $notes
		);

		return $this->renderJSON($params);	
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function get() {
		$id = (int) $this->params('id');
		$note = $this->businessLayer->getNote($id);

		$params = array(
			'notes' => array($note)
		);

		return $this->renderJSON($params);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function save() {
		$oldTitle = $this->params('oldTitle');
		$newTitle = $this->params('newTitle');
		$content = $this->params('content');

		$this->businessLayer->saveNote($oldTitle, $newTitle, $content);

		return $this->renderJSON();
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function delete() {
		$id = (int) $this->params('id');

		$this->businessLayer->deleteNote($id);
	
		return $this->renderJSON();	
	}


}