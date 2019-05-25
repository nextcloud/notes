<?php

namespace OCA\Notes\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IL10N;
use OCP\AppFramework\Http\DataResponse;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\SettingsService;

/**
 * Class NotesController
 *
 * @package OCA\Notes\Controller
 */
class NotesController extends Controller {

	use Errors;

	/** @var NotesService */
	private $notesService;
	/** @var SettingsService */
	private $settingsService;
	/** @var IConfig */
	private $settings;
	/** @var string */
	private $userId;
	/** @var IL10N */
	private $l10n;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param NotesService $notesService
	 * @param SettingsService $settingsService
	 * @param IConfig $settings
	 * @param IL10N $l10n
	 * @param string $UserId
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		NotesService $notesService,
		SettingsService $settingsService,
		IConfig $settings,
		IL10N $l10n,
		$UserId
	) {
		parent::__construct($AppName, $request);
		$this->notesService = $notesService;
		$this->settingsService = $settingsService;
		$this->settings = $settings;
		$this->userId = $UserId;
		$this->l10n = $l10n;
	}


	/**
	 * @NoAdminRequired
	 */
	public function index() {
		$notes = $this->notesService->getAll($this->userId, true);
		$settings = $this->settingsService->getAll($this->userId);

		$errorMessage = null;
		$lastViewedNote = (int) $this->settings->getUserValue(
			$this->userId,
			$this->appName,
			'notesLastViewedNote'
		);
		// check if notes folder is accessible
		try {
			$this->notesService->checkNotesFolder($this->userId);
			if ($lastViewedNote) {
				// check if note exists
				try {
					$this->notesService->get($lastViewedNote, $this->userId);
				} catch (\Exception $ex) {
					$this->settings->deleteUserValue($this->userId, $this->appName, 'notesLastViewedNote');
					$lastViewedNote = 0;
					$errorMessage = $this->l10n->t('The last viewed note cannot be accessed. ').$ex->getMessage();
				}
			}
		} catch (\Exception $e) {
			$errorMessage = $this->l10n->t('The notes folder is not accessible: %s', $e->getMessage());
		}

		return new DataResponse([
			'notes' => $notes,
			'settings' => $settings,
			'lastViewedNote' => $lastViewedNote,
			'errorMessage' => $errorMessage,
		]);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function get($id) {
		// save the last viewed note
		$this->settings->setUserValue(
			$this->userId,
			$this->appName,
			'notesLastViewedNote',
			$id
		);

		return $this->respond(function () use ($id) {
			return $this->notesService->get($id, $this->userId);
		});
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $content
	 */
	public function create($content = '', $category = null) {
		$note = $this->notesService->create($this->userId);
		$note = $this->notesService->update(
			$note->getId(),
			$content,
			$this->userId,
			$category
		);
		return new DataResponse($note);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $content
	 * @return DataResponse
	 */
	public function update($id, $content) {
		return $this->respond(function () use ($id, $content) {
			return $this->notesService->update($id, $content, $this->userId);
		});
	}



	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $category
	 * @return DataResponse
	 */
	public function category($id, $category) {
		return $this->respond(function () use ($id, $category) {
			$note = $this->notesService->update($id, null, $this->userId, $category);
			return $note->category;
		});
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param boolean $favorite
	 * @return DataResponse
	 */
	public function favorite($id, $favorite) {
		return $this->respond(function () use ($id, $favorite) {
			return $this->notesService->favorite($id, $favorite, $this->userId);
		});
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function destroy($id) {
		return $this->respond(function () use ($id) {
			$this->notesService->delete($id, $this->userId);
			return [];
		});
	}
}
