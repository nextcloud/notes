<?php

namespace OCA\Notes\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IL10N;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\SettingsService;
use OCA\Notes\Service\InsufficientStorageException;

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
		$settings = $this->settingsService->getAll($this->userId);

		$errorMessage = null;
		$lastViewedNote = (int) $this->settings->getUserValue(
			$this->userId,
			$this->appName,
			'notesLastViewedNote'
		);
		// check if notes folder is accessible
		$notes = null;
		try {
			$notes = $this->notesService->getAll($this->userId, true);
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
			strval($id)
		);

		$note = $this->notesService->get($id, $this->userId);
		return new DataResponse($note);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $content
	 */
	public function create($content = '', $category = null) {
		try {
			$note = $this->notesService->create($this->userId);
			$note = $this->notesService->update(
				$note->getId(),
				$content,
				$this->userId,
				$category
			);
			return new DataResponse($note);
		} catch (InsufficientStorageException $e) {
			return new DataResponse([], Http::STATUS_INSUFFICIENT_STORAGE);
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $content
	 */
	public function undo($id, $content, $category, $modified, $favorite) {
		try {
			// check if note still exists
			$note = $this->notesService->get($id, $this->userId);
			if ($note->getError()) {
				throw new \Exception();
			}
		} catch (\Throwable $e) {
			// re-create if note doesn't exit anymore
			$note = $this->notesService->create($this->userId);
			$note = $this->notesService->update(
				$note->getId(),
				$content,
				$this->userId,
				$category,
				$modified
			);
			$note->favorite = $this->notesService->favorite($note->getId(), $favorite, $this->userId);
		}
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
		try {
			$note = $this->notesService->update($id, $content, $this->userId);
			return new DataResponse($note);
		} catch (InsufficientStorageException $e) {
			return new DataResponse([], Http::STATUS_INSUFFICIENT_STORAGE);
		}
	}



	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $category
	 * @return DataResponse
	 */
	public function category($id, $category) {
		$note = $this->notesService->update($id, null, $this->userId, $category);
		return new DataResponse($note->category);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param boolean $favorite
	 * @return DataResponse
	 */
	public function favorite($id, $favorite) {
		$result = $this->notesService->favorite($id, $favorite, $this->userId);
		return new DataResponse($result); // @phan-suppress-current-line PhanTypeMismatchArgument
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function destroy($id) {
		$this->notesService->delete($id, $this->userId);
		return new DataResponse([]);
	}
}
