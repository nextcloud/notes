<?php

declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\MetaService;
use OCA\Notes\Service\SettingsService;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\Files\IRootFolder;
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
	/** @var IRootFolder */
	private $root;

	public function __construct(
		string $AppName,
		IRequest $request,
		NotesService $notesService,
		MetaService $metaService,
		SettingsService $settingsService,
		Helper $helper,
		IConfig $settings,
		IL10N $l10n,
		IRootFolder $root
	) {
		parent::__construct($AppName, $request);
		$this->notesService = $notesService;
		$this->metaService = $metaService;
		$this->settingsService = $settingsService;
		$this->helper = $helper;
		$this->settings = $settings;
		$this->l10n = $l10n;
		$this->root = $root;
	}

	/**
	 * @NoAdminRequired
	 */
	public function index(int $pruneBefore = 0) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($pruneBefore) {
			$userId = $this->helper->getUID();
			$settings = $this->settingsService->getAll($userId);

			$lastViewedNote = (int) $this->settings->getUserValue(
				$userId,
				$this->appName,
				'notesLastViewedNote'
			);
			$errorMessage = null;
			$nac = null;

			try {
				$nac = $this->helper->getNotesAndCategories($pruneBefore, [ 'etag', 'content' ]);
			} catch (\Throwable $e) {
				$this->helper->logException($e);
				$errorMessage = $this->l10n->t('Reading notes from filesystem has failed.').' ('.get_class($e).')';
			}

			if ($errorMessage === null && $lastViewedNote
				&& is_array($nac) && is_array($nac['notesAll']) && !count($nac['notesAll'])
			) {
				$this->settings->deleteUserValue($userId, $this->appName, 'notesLastViewedNote');
				$lastViewedNote = 0;
			}

			$result = [
				'notesData' => $nac ? array_values($nac['notesData']) : null,
				'noteIds' => $nac ? array_keys($nac['notesAll']) : null,
				'categories' => $nac['categories'] ?? null,
				'settings' => $settings,
				'lastViewedNote' => $lastViewedNote,
				'errorMessage' => $errorMessage,
			];
			$etag = md5(json_encode($result));
			return (new JSONResponse($result))
				->setLastModified($nac['lastUpdate'] ?? null)
				->setETag($etag)
			;
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function dashboard() : JSONResponse {
		return $this->helper->handleErrorResponse(function () {
			$maxItems = 7;
			$userId = $this->helper->getUID();
			$notes = $this->notesService->getTopNotes($userId, $maxItems + 1);
			$hasMoreNotes = count($notes) > $maxItems;
			$notes = array_slice($notes, 0, $maxItems);
			$items = array_map(function ($note) {
				$excerpt = '';
				try {
					$excerpt = $note->getExcerpt();
				} catch (\Throwable $e) {
				}
				return [
					'id' => $note->getId(),
					'title' => $note->getTitle(),
					'category' => $note->getCategory(),
					'favorite' => $note->getFavorite(),
					'excerpt' => $excerpt,
				];
			}, $notes);
			return [
				'items' => $items,
				'hasMoreItems' => $hasMoreNotes,
			];
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

			$noteData = $this->helper->getNoteData($note);
			return (new JSONResponse($noteData))
				->setETag($noteData['etag'])
			;
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function create(string $category) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($category) {
			$note = $this->notesService->create($this->helper->getUID(), '', $category);
			return $this->helper->getNoteData($note);
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
				$noteData = $this->helper->getNoteData($note);
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
				return $this->helper->getNoteData($note);
			}
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function autotitle(int $id) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($id) {
			$note = $this->notesService->get($this->helper->getUID(), $id);
			$oldTitle = $note->getTitle();
			$newTitle = $this->notesService->getTitleFromContent($note->getContent());
			if ($oldTitle !== $newTitle) {
				$note->setTitle($newTitle);
			}
			return $note->getTitle();
		});
	}


	/**
	 * @NoAdminRequired
	 */
	public function update(int $id, string $content) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($id, $content) {
			$note = $this->helper->getNoteWithETagCheck($id, $this->request);
			$note->setContent($content);
			return $this->helper->getNoteData($note);
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

	/**
	 * With help from: https://github.com/nextcloud/cookbook
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse|FileDisplayResponse
	 */
	public function getImage(int $id, string $path) {
		try {
			$note = $this->notesService->get($this->helper->getUID(), $id);


			$notesFolderPath = $this->notesService->getNotesFolder($this->helper->getUID())->getPath();
			$notePath = $notesFolderPath . "/" . $note->getCategory() . "/";


			//because of how the internet works, ../ cannot work in the url and get's removed. We use ;;/ as a placeholder.
			$parentcount = substr_count($path, ';;/');
			$path = str_replace(";;/", "", $path);

			$relativeImageNode = $this->root->get($notePath);
			for ($i = 0; $i < $parentcount; $i++) {
				$relativeImageNode = $relativeImageNode->getParent();
			}

			foreach ($relativeImageNode->getDirectoryListing() as $file) {
				if (str_ends_with($file->getPath(), $path)) {
					//make sure the file is in the notes folder
					if (str_starts_with($file->getPath(), $notesFolderPath)) {
						return new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'public, max-age=604800']);
					}
				}
			}
		} catch (\Exception $e) {
			return new JSONResponse($e->getMessage());
		}
	}

	/**
	* @NoAdminRequired
	*/
	public function createImage($id): JSONResponse
	{
		$file = $this->request->getUploadedFile('image');
		$result = $this->notesService->createImage(
			$this->helper->getUID(),
			intval($id),
			$file
		);

		return new JSONResponse($result);
	}


}
