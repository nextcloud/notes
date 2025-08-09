<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013 Bernhard Posselt <nukeawhale@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Controller;

use OCA\Notes\Service\MetaNote;
use OCA\Notes\Service\MetaService;
use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\SettingsService;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Files\IMimeTypeDetector;
use OCP\IRequest;

class NotesApiController extends ApiController {
	private NotesService $service;
	private MetaService $metaService;
	private SettingsService $settingsService;
	private Helper $helper;
	private IMimeTypeDetector $mimeTypeDetector;

	public function __construct(
		string $AppName,
		IRequest $request,
		NotesService $service,
		MetaService $metaService,
		SettingsService $settingsService,
		Helper $helper,
		IMimeTypeDetector $mimeTypeDetector,
	) {
		parent::__construct($AppName, $request);
		$this->service = $service;
		$this->metaService = $metaService;
		$this->settingsService = $settingsService;
		$this->helper = $helper;
		$this->mimeTypeDetector = $mimeTypeDetector;
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function index(
		?string $category = null,
		string $exclude = '',
		int $pruneBefore = 0,
		int $chunkSize = 0,
		?string $chunkCursor = null,
	) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use (
			$category,
			$exclude,
			$pruneBefore,
			$chunkSize,
			$chunkCursor
		) {
			// initialize settings
			$userId = $this->helper->getUID();
			$this->settingsService->getAll($userId, true);
			// load notes and categories
			$exclude = explode(',', $exclude);
			$data = $this->helper->getNotesAndCategories($pruneBefore, $exclude, $category, $chunkSize, $chunkCursor);
			$notesData = $data['notesData'];
			if (!$data['chunkCursor']) {
				// if last chunk, then send all notes (pruned)
				$notesData += array_map(function (MetaNote $m) {
					return [ 'id' => $m->note->getId() ];
				}, $data['notesAll']);
			}
			$response = new JSONResponse(array_values($notesData));
			$response->setLastModified($data['lastUpdate']);
			$response->setETag(md5(json_encode($notesData)));
			if ($data['chunkCursor']) {
				$response->addHeader('X-Notes-Chunk-Cursor', $data['chunkCursor']->toString());
				$response->addHeader('X-Notes-Chunk-Pending', $data['numPendingNotes']);
			}
			return $response;
		});
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function get(int $id, string $exclude = '') : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($id, $exclude) {
			$exclude = explode(',', $exclude);
			$note = $this->service->get($this->helper->getUID(), $id);
			$noteData = $this->helper->getNoteData($note, $exclude);
			return (new JSONResponse($noteData))
				->setETag($noteData['etag'])
			;
		});
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function create(
		string $category = '',
		string $title = '',
		string $content = '',
		int $modified = 0,
		bool $favorite = false,
	) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($category, $title, $content, $modified, $favorite) {
			$note = $this->service->create($this->helper->getUID(), $title, $category);
			try {
				$note->setContent($content);
				if ($modified) {
					$note->setModified($modified);
				}
				if ($favorite) {
					$note->setFavorite($favorite);
				}
			} catch (\Throwable $e) {
				// roll-back note creation
				$this->service->delete($this->helper->getUID(), $note->getId());
				throw $e;
			}
			return $this->helper->getNoteData($note);
		});
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @deprecated this was used in API v0.2 only, use #create() instead
	 */
	public function createAutoTitle(
		string $category = '',
		string $content = '',
		int $modified = 0,
		bool $favorite = false,
	) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($category, $content, $modified, $favorite) {
			$title = $this->service->getTitleFromContent($content);
			return $this->create($category, $title, $content, $modified, $favorite);
		});
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function update(
		int $id,
		?string $content = null,
		?int $modified = null,
		?string $title = null,
		?string $category = null,
		?bool $favorite = null,
	) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use (
			$id,
			$content,
			$modified,
			$title,
			$category,
			$favorite
		) {
			$note = $this->helper->getNoteWithETagCheck($id, $this->request);
			if ($content !== null && $content !== $note->getContent()) {
				$note->setContent($content);
			}
			if ($modified !== null && $modified !== $note->getModified()) {
				$note->setModified($modified);
			}
			if ($title !== null && $title !== $note->getTitle()) {
				$note->setTitleCategory($title, $category);
			} elseif ($category !== null && $category !== $note->getCategory()) {
				$note->setCategory($category);
			}
			if ($favorite !== null && $favorite !== $note->getFavorite()) {
				$note->setFavorite($favorite);
			}
			return $this->helper->getNoteData($note);
		});
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @deprecated this was used in API v0.2 only, use #update() instead
	 */
	public function updateAutoTitle(
		int $id,
		?string $content = null,
		?int $modified = null,
		?string $category = null,
		?bool $favorite = null,
	) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($id, $content, $modified, $category, $favorite) {
			if ($content === null) {
				$note = $this->service->get($this->helper->getUID(), $id);
				$title = $this->service->getTitleFromContent($note->getContent());
			} else {
				$title = $this->service->getTitleFromContent($content);
			}
			return $this->update($id, $content, $modified, $title, $category, $favorite);
		});
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function destroy(int $id) : JSONResponse {
		return $this->helper->handleErrorResponse(function () use ($id) {
			$this->service->delete($this->helper->getUID(), $id);
			return [];
		});
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function setSettings() : JSONResponse {
		return $this->helper->handleErrorResponse(function () {
			$this->settingsService->setPublic($this->helper->getUID(), $this->request->getParams());
			return $this->getSettings();
		});
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function getSettings() : JSONResponse {
		return $this->helper->handleErrorResponse(function () {
			return $this->settingsService->getPublic($this->helper->getUID());
		});
	}
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function fail() : JSONResponse {
		return $this->helper->handleErrorResponse(function () {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		});
	}



	/**
	 * With help from: https://github.com/nextcloud/cookbook
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @return JSONResponse|StreamResponse
	 */
	public function getAttachment(int $noteid, string $path): Http\Response {
		try {
			$targetimage = $this->service->getAttachment(
				$this->helper->getUID(),
				$noteid,
				$path
			);
			$fileHandle = $targetimage->fopen('rb');
			if ($fileHandle === false) {
				throw new \Exception('Could not open file');
			}
			$response = new StreamResponse($fileHandle);
			$response->addHeader('Content-Disposition', 'attachment; filename="' . rawurldecode($targetimage->getName()) . '"');
			$response->addHeader('Content-Type', $this->mimeTypeDetector->getSecureMimeType($targetimage->getMimeType()));
			$response->addHeader('Cache-Control', 'public, max-age=604800');
			return $response;
		} catch (\Exception $e) {
			$this->helper->logException($e);
			return $this->helper->createErrorResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function uploadFile(int $noteid): JSONResponse {
		$file = $this->request->getUploadedFile('file');
		return $this->helper->handleErrorResponse(function () use ($noteid, $file): array {
			return $this->service->createImage(
				$this->helper->getUID(),
				$noteid,
				$file
			);
		});
	}

}
