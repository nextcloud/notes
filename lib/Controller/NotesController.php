<?php declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\MetaService;
use OCA\Notes\Service\SettingsService;
use OCA\Notes\Service\NoteDoesNotExistException;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IL10N;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

class NotesController extends Controller {

	/** @var NotesService */
	private $notesService;
	/** @var MetaService */
	private $metaService;
	/** @var SettingsService */
	private $settingsService;
	/** @var Helper */
	private $helper;
	/** @var IConfig */
	private $settings;
	/** @var IL10N */
	private $l10n;

	public function __construct(
		string $AppName,
		IRequest $request,
		NotesService $notesService,
		MetaService $metaService,
		SettingsService $settingsService,
		Helper $helper,
		IConfig $settings,
		IL10N $l10n
	) {
		parent::__construct($AppName, $request);
		$this->notesService = $notesService;
		$this->metaService = $metaService;
		$this->settingsService = $settingsService;
		$this->helper = $helper;
		$this->settings = $settings;
		$this->l10n = $l10n;
	}


	/**
	 * @NoAdminRequired
	 */
	public function index(int $pruneBefore = 0) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($pruneBefore) {
			$userId = $this->helper->getUID();
			$now = new \DateTime(); // this must be before loading notes if there are concurrent changes possible
			$settings = $this->settingsService->getAll($userId);

			$errorMessage = null;
			$lastViewedNote = (int) $this->settings->getUserValue(
				$userId,
				$this->appName,
				'notesLastViewedNote'
			);
			// check if notes folder is accessible
			$notesData = null;
			$categories = null;
			try {
				$data = $this->notesService->getAll($userId);
				$metas = $this->metaService->updateAll($userId, $data['notes']);
				$categories = $data['categories'];
				$notesData = array_map(function ($note) use ($metas, $pruneBefore) {
					$lastUpdate = $metas[$note->getId()]->getLastUpdate();
					if ($pruneBefore && $lastUpdate<$pruneBefore) {
						return [ 'id' => $note->getId() ];
					} else {
						return $note->getData([ 'content' ]);
					}
				}, $data['notes']);
				if ($lastViewedNote) {
					// check if note exists
					try {
						$this->notesService->get($userId, $lastViewedNote);
					} catch (\Throwable $ex) {
						if (!($ex instanceof NoteDoesNotExistException)) {
							$this->helper->logException($ex);
						}
						$this->settings->deleteUserValue($userId, $this->appName, 'notesLastViewedNote');
						$lastViewedNote = 0;
						$errorMessage = $this->l10n->t('The last viewed note cannot be accessed. ').$ex->getMessage();
					}
				}
			} catch (\Throwable $e) {
				$this->helper->logException($e);
				$errorMessage = $this->l10n->t('The notes folder is not accessible: %s', $e->getMessage());
			}

			$result = [
				'notes' => $notesData,
				'categories' => $categories,
				'settings' => $settings,
				'lastViewedNote' => $lastViewedNote,
				'errorMessage' => $errorMessage,
			];
			$etag = md5(json_encode($result));
			return (new JSONResponse($result))
				->setLastModified($now)
				->setETag($etag)
			;
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function get(int $id) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($id) {
			$note = $this->notesService->get($this->helper->getUID(), $id);

			// save the last viewed note
			$this->settings->setUserValue(
				$this->helper->getUID(),
				$this->appName,
				'notesLastViewedNote',
				strval($id)
			);

			$result = $note->getData();
			$etag = md5(json_encode($result));
			return (new JSONResponse($result))
				->setETag($etag)
			;
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function create(string $category) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($category) {
			$note = $this->notesService->create($this->helper->getUID(), '', $category);
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
				$note = $this->notesService->get($this->helper->getUID(), $id);
				$noteData = $note->getData();
				if ($noteData['error']) {
					throw new \Exception();
				}
				return $noteData;
			} catch (\Throwable $e) {
				// re-create if note doesn't exit anymore
				$note = $this->notesService->create($this->helper->getUID(), $title, $category);
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
			$note = $this->notesService->get($this->helper->getUID(), $id);
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
			$note = $this->notesService->get($this->helper->getUID(), $id);
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
			$this->notesService->delete($this->helper->getUID(), $id);
			return [];
		});
	}
}
