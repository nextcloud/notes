<?php declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\SettingsService;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IL10N;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

class NotesController extends Controller {

	/** @var NotesService */
	private $notesService;
	/** @var SettingsService */
	private $settingsService;
	/** @var Helper */
	private $helper;
	/** @var IConfig */
	private $settings;
	/** @var string */
	private $userId;
	/** @var IL10N */
	private $l10n;

	public function __construct(
		string $AppName,
		IRequest $request,
		NotesService $notesService,
		SettingsService $settingsService,
		Helper $helper,
		IConfig $settings,
		IL10N $l10n,
		string $UserId
	) {
		parent::__construct($AppName, $request);
		$this->notesService = $notesService;
		$this->settingsService = $settingsService;
		$this->helper = $helper;
		$this->settings = $settings;
		$this->userId = $UserId;
		$this->l10n = $l10n;
	}


	/**
	 * @NoAdminRequired
	 */
	public function index() : JSONResponse {
		return $this->helper->handleErrorResponse(function () {
			$settings = $this->settingsService->getAll($this->userId);

			$errorMessage = null;
			$lastViewedNote = (int) $this->settings->getUserValue(
				$this->userId,
				$this->appName,
				'notesLastViewedNote'
			);
			// check if notes folder is accessible
			$notesData = null;
			$categories = null;
			try {
				$data = $this->notesService->getAll($this->userId);
				$categories = $data['categories'];
				$notesData = array_map(function ($note) {
					return $note->getData([ 'content' ]);
				}, $data['notes']);
				if ($lastViewedNote) {
					// check if note exists
					try {
						$this->notesService->get($this->userId, $lastViewedNote);
					} catch (\Exception $ex) {
						$this->settings->deleteUserValue($this->userId, $this->appName, 'notesLastViewedNote');
						$lastViewedNote = 0;
						$errorMessage = $this->l10n->t('The last viewed note cannot be accessed. ').$ex->getMessage();
					}
				}
			} catch (\Exception $e) {
				$errorMessage = $this->l10n->t('The notes folder is not accessible: %s', $e->getMessage());
			}

			return [
				'notes' => $notesData,
				'categories' => $categories,
				'settings' => $settings,
				'lastViewedNote' => $lastViewedNote,
				'errorMessage' => $errorMessage,
			];
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function get(int $id) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($id) {
			$note = $this->notesService->get($this->userId, $id);

			// save the last viewed note
			$this->settings->setUserValue(
				$this->userId,
				$this->appName,
				'notesLastViewedNote',
				strval($id)
			);

			return $note->getData();
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function create(string $category) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($category) {
			$note = $this->notesService->create($this->userId, '', $category);
			$note->setContent('');
			return $note->getData();
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function undo(
		int $id,
		string $title,
		string $content,
		string $category,
		int $modified,
		bool $favorite
	) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use (
			$id,
			$title,
			$content,
			$category,
			$modified,
			$favorite
		) {
			try {
				// check if note still exists
				$note = $this->notesService->get($this->userId, $id);
				$noteData = $note->getData();
				if ($noteData['error']) {
					throw new \Exception();
				}
				return $noteData;
			} catch (\Throwable $e) {
				// re-create if note doesn't exit anymore
				$note = $this->notesService->create($this->userId, $title, $category);
				$note->setContent($content);
				$note->setModified($modified);
				$note->setFavorite($favorite);
				return $note->getData();
			}
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function update(int $id, string $content, bool $autotitle) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($id, $content, $autotitle) {
			$note = $this->notesService->get($this->userId, $id);
			$note->setContent($content);
			if ($autotitle) {
				$title = $this->notesService->getTitleFromContent($content);
				$note->setTitle($title);
			}
			return $note->getData();
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function updateProperty(
		int $id,
		string $property,
		?int $modified = null,
		?string $title = null,
		?string $category = null,
		?bool $favorite = null
	) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use (
			$id,
			$property,
			$modified,
			$title,
			$category,
			$favorite
		) {
			$note = $this->notesService->get($this->userId, $id);
			$result = null;
			switch ($property) {
				case 'modified':
					if ($modified !== null) {
						$note->setModified($modified);
					}
					$result = $note->getModified();
					break;

				case 'title':
					if ($title !== null) {
						$note->setTitle($title);
					}
					$result = $note->getTitle();
					break;

				case 'category':
					if ($category !== null) {
						$note->setCategory($category);
					}
					$result = $note->getCategory();
					break;

				case 'favorite':
					if ($favorite !== null) {
						$note->setFavorite($favorite);
					}
					$result = $note->getFavorite();
					break;

				default:
					return new JSONResponse([], Http::STATUS_BAD_REQUEST);
			}
			return $result;
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function destroy(int $id) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($id) {
			$this->notesService->delete($this->userId, $id);
			return [];
		});
	}
}
