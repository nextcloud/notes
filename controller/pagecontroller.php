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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\NoteDoesNotExistException;

/**
 * Class PageController
 *
 * @package OCA\Notes\Controller
 */
class PageController extends Controller {

	/** @var NotesService */
	private $notesService;
	/** @var IConfig */
	private $settings;
	/** @var string */
	private $userId;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param NotesService $notesService
	 * @param IConfig $settings
	 * @param string $UserId
	 */
	public function __construct($AppName,
								IRequest $request,
								NotesService $notesService,
								IConfig $settings,
								$UserId){
		parent::__construct($AppName, $request);
		$this->notesService = $notesService;
		$this->settings = $settings;
		$this->userId = $UserId;
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function index() {
		$lastViewedNote = (int) $this->settings->getUserValue($this->userId,
			$this->appName, 'notesLastViewedNote');
		// check if note exists
		try {
			$this->notesService->get($lastViewedNote, $this->userId);
		} catch(NoteDoesNotExistException $ex) {
			$lastViewedNote = 0;
		}

		return new TemplateResponse(
			$this->appName,
			'main',
			[
				'lastViewedNote' => $lastViewedNote
			]
		);
	}


}