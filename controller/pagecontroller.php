<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Controller;

use \OCP\AppFramework\Controller;
use \OCP\IRequest;

use \OCA\Notes\Core\API;
use \OCA\Notes\Service\NotesService;
use \OCA\Notes\Service\NoteDoesNotExistException;


class PageController extends Controller {

	private $notesService;

	public function __construct(API $api,
	                            IRequest $request,
	                            NotesService $notesService){
		parent::__construct($api, $request);
		$this->notesService = $notesService;
	}


	/**
	 * ATTENTION!!!
	 * The following comments turn off security checks
	 * Please look up their meaning in the documentation:
	 * http://doc.owncloud.org/server/master/developer_manual/app/appframework/controllers.html
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$lastViewedNote = (int) $this->api->getUserValue('notesLastViewedNote');
		// check if note exists
		try {
			$this->notesService->get($lastViewedNote);
		} catch(NoteDoesNotExistException $ex) {
			$lastViewedNote = 0;
		}

		return $this->render('main', array(
			'lastViewedNote' => $lastViewedNote
		));
	}


}