<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013 Bernhard Posselt <nukeawhale@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Controller;

use OCA\Notes\Service\Note;
use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\SettingsService;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;

class NotesController extends Controller {
	private NotesService $notesService;
	private SettingsService $settingsService;
	private ILockManager $lockManager;
	private Helper $helper;
	private IConfig $settings;
	private IL10N $l10n;
	private IMimeTypeDetector $mimeTypeDetector;

	public function __construct(
		string $AppName,
		IRequest $request,
		NotesService $notesService,
		ILockManager $lockManager,
		SettingsService $settingsService,
		Helper $helper,
		IConfig $settings,
		IL10N $l10n,
		IMimeTypeDetector $mimeTypeDetector,
	) {
		parent::__construct($AppName, $request);
		$this->notesService = $notesService;
		$this->settingsService = $settingsService;
		$this->lockManager = $lockManager;
		$this->helper = $helper;
		$this->settings = $settings;
		$this->l10n = $l10n;
		$this->mimeTypeDetector = $mimeTypeDetector;
	}

	/**
	 * @NoAdminRequired
	 */
	public function index(int $pruneBefore = 0) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($pruneBefore) {
			$userId = $this->helper->getUID();
			// initialize and load settings
			$settings = $this->settingsService->getAll($userId, true);

			$lastViewedNote = (int)$this->settings->getUserValue(
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
				$errorMessage = $this->l10n->t('Reading notes from filesystem has failed.') . ' (' . get_class($e) . ')';
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
			$maxItems = 6;
			$userId = $this->helper->getUID();
			$notes = $this->notesService->getTopNotes($userId);
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
	public function create(string $category = '', string $content = '', string $title = '') : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($category, $content, $title) {
			$note = $this->notesService->create($this->helper->getUID(), $title, $category);
			if ($content) {
				$note->setContent($content);
			}
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
		bool $favorite,
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
				$this->inLockScope($note, function () use ($note, $newTitle) {
					$note->setTitle($newTitle);
				});
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
		?bool $favorite = null,
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
						$this->inLockScope($note, function () use ($note, $title) {
							$note->setTitle($title);
						});
					}
					$result = [
						'title' => $note->getTitle(),
						'internalPath' => $note->getData()['internalPath'], // based on the title
					];
					break;

				case 'category':
					if ($category !== null) {
						$this->inLockScope($note, function () use ($note, $category) {
							$note->setCategory($category);
						});
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
	 * @return JSONResponse|StreamResponse
	 */
	public function getAttachment(int $noteid, string $path): Http\Response {
		try {
			$targetimage = $this->notesService->getAttachment(
				$this->helper->getUID(),
				$noteid,
				$path
			);
			$response = new StreamResponse($targetimage->fopen('rb'));
			$response->addHeader(
				'Content-Disposition',
				'attachment; filename="' . rawurldecode($targetimage->getName()) . '"'
			);
			$response->addHeader(
				'Content-Type',
				$this->mimeTypeDetector->getSecureMimeType($targetimage->getMimeType())
			);
			$response->addHeader('Cache-Control', 'public, max-age=604800');
			return $response;
		} catch (\Exception $e) {
			$this->helper->logException($e);
			return $this->helper->createErrorResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function uploadFile(int $noteid): JSONResponse {
		$file = $this->request->getUploadedFile('file');
		return $this->helper->handleErrorResponse(function () use ($noteid, $file) {
			return $this->notesService->createImage(
				$this->helper->getUID(),
				$noteid,
				$file
			);
		});
	}

	private function inLockScope(Note $note, callable $callback) {
		$isRichText = $this->settingsService->get($this->helper->getUID(), 'noteMode') === 'rich';
		$lockContext = new LockContext(
			$note->getFile(),
			$isRichText ? ILock::TYPE_APP : ILock::TYPE_USER,
			$isRichText ? 'text' : $this->helper->getUID()
		);
		$this->lockManager->runInScope($lockContext, function () use ($callback) {
			$callback();
		});
	}
}
