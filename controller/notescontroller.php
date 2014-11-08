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
use \OCP\IRequest;
use \OCP\IConfig;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http;

use \OCA\Notes\Service\NotesService;
use \OCA\Notes\Service\NoteDoesNotExistException;


class NotesController extends Controller {

	private $notesService;
	private $settings;
	private $userId;

	public function __construct($appName,
	                            IRequest $request,
		                        NotesService $notesService,
		                        IConfig $settings,
		                        $userId){
		parent::__construct($appName, $request);
		$this->notesService = $notesService;
		$this->settings = $settings;
		$this->userId = $userId;
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
	 *
	 * @param int $id
	 */
	public function get($id) {
		// save the last viewed note
		$this->settings->setUserValue($this->userId, $this->appName,
			'notesLastViewedNote', $id);
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


	/**
	 * @NoAdminRequired
	 */
	public function getConfig() {
		$markdown = $this->settings->getUserValue($this->userId,
			$this->appName, 'notesMarkdown') === '1';
		$config = array(
			'markdown' => $markdown
		);

		return new JSONResponse($config);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $markdown
	 */
	public function setConfig($markdown) {
		$markdown = $this->settings->setUserValue($this->userId,
			$this->appName, 'notesMarkdown', $markdown);
		return new JSONResponse();
	}


}