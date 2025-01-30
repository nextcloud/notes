<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Controller;

use OCA\Notes\AppInfo\Application;
use OCA\Notes\Db\Meta;
use OCA\Notes\Service\MetaNote;
use OCA\Notes\Service\MetaService;
use OCA\Notes\Service\Note;
use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\Util;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

use Psr\Log\LoggerInterface;

class Helper {
	private NotesService $notesService;
	private MetaService $metaService;
	public LoggerInterface $logger;
	private IUserSession $userSession;

	public function __construct(
		NotesService $notesService,
		MetaService $metaService,
		IUserSession $userSession,
		LoggerInterface $logger,
	) {
		$this->notesService = $notesService;
		$this->metaService = $metaService;
		$this->userSession = $userSession;
		$this->logger = $logger;
	}

	public function getUID() : string {
		return $this->userSession->getUser()->getUID();
	}

	public function getNoteWithETagCheck(int $id, IRequest $request) : Note {
		$userId = $this->getUID();
		$note = $this->notesService->get($userId, $id);
		$ifMatch = $request->getHeader('If-Match');
		if ($ifMatch) {
			$matchEtags = preg_split('/,\s*/', $ifMatch);
			$meta = $this->metaService->update($userId, $note);
			if (!in_array('"' . $meta->getEtag() . '"', $matchEtags)) {
				throw new ETagDoesNotMatchException($note);
			}
		}
		return $note;
	}

	public function getNoteData(Note $note, array $exclude = [], ?Meta $meta = null) : array {
		if ($meta === null) {
			$meta = $this->metaService->update($this->getUID(), $note);
		}
		$data = $note->getData($exclude);
		$data['etag'] = $meta->getEtag();
		return $data;
	}

	public function getNotesAndCategories(
		int $pruneBefore,
		array $exclude,
		?string $category = null,
		int $chunkSize = 0,
		?string $chunkCursorStr = null,
	) : array {
		$userId = $this->getUID();
		$chunkCursor = $chunkCursorStr ? ChunkCursor::fromString($chunkCursorStr) : null;
		$lastUpdate = $chunkCursor->timeStart ?? new \DateTime();
		$data = $this->notesService->getAll($userId, true); // auto-create notes folder if not exists
		$metaNotes = $this->metaService->getAll($userId, $data['notes']);

		// if a category is requested, then ignore all other notes
		if ($category !== null) {
			$metaNotes = array_filter($metaNotes, function (MetaNote $m) use ($category) {
				return $m->note->getCategory() === $category;
			});
		}

		// list of notes that should be sent to the client
		$fullNotes = array_filter($metaNotes, function (MetaNote $m) use ($pruneBefore, $chunkCursor) {
			$isPruned = $pruneBefore && $m->meta->getLastUpdate() < $pruneBefore;
			$noteLastUpdate = (int)$m->meta->getLastUpdate();
			$isBeforeCursor = $chunkCursor && (
				$noteLastUpdate < $chunkCursor->noteLastUpdate
				|| ($noteLastUpdate === $chunkCursor->noteLastUpdate
				&& $m->note->getId() <= $chunkCursor->noteId)
			);
			return !$isPruned && !$isBeforeCursor;
		});

		// sort the list for slicing the next chunk
		uasort($fullNotes, function (MetaNote $a, MetaNote $b) {
			return $a->meta->getLastUpdate() <=> $b->meta->getLastUpdate()
				?: $a->note->getId() <=> $b->note->getId();
		});

		// slice the next chunk
		$chunkedNotes = $chunkSize ? array_slice($fullNotes, 0, $chunkSize, true) : $fullNotes;
		$numPendingNotes = count($fullNotes) - count($chunkedNotes);

		// if the chunk does not contain all remaining notes, then generate new chunk cursor
		$newChunkCursor = $numPendingNotes ? ChunkCursor::fromNote($lastUpdate, end($chunkedNotes)) : null;

		// load data for the current chunk
		$notesData = array_map(function (MetaNote $m) use ($exclude) {
			return $this->getNoteData($m->note, $exclude, $m->meta);
		}, $chunkedNotes);

		return [
			'categories' => $data['categories'],
			'notesAll' => $metaNotes,
			'notesData' => $notesData,
			'lastUpdate' => $lastUpdate,
			'chunkCursor' => $newChunkCursor,
			'numPendingNotes' => $numPendingNotes,
		];
	}

	public function logException(\Throwable $e) : void {
		$this->logger->error('Controller failed with ' . get_class($e), [ 'exception' => $e ]);
	}

	/** @param 200|201|400|403|404|423|500|507 $statusCode */
	public function createErrorResponse(\Throwable $e, int $statusCode) : JSONResponse {
		$response = [
			'errorType' => get_class($e)
		];
		return new JSONResponse($response, $statusCode);
	}

	public function handleErrorResponse(callable $respond) : JSONResponse {
		try {
			$data = Util::retryIfLocked($respond);
			$response = $data instanceof JSONResponse ? $data : new JSONResponse($data);
		} catch (\OCA\Notes\Controller\ETagDoesNotMatchException $e) {
			$response = new JSONResponse($this->getNoteData($e->note), Http::STATUS_PRECONDITION_FAILED);
		} catch (\OCA\Notes\Service\NoteDoesNotExistException $e) {
			$this->logException($e);
			$response = $this->createErrorResponse($e, Http::STATUS_NOT_FOUND);
		} catch (\OCA\Notes\Service\InsufficientStorageException $e) {
			$this->logException($e);
			$response = $this->createErrorResponse($e, Http::STATUS_INSUFFICIENT_STORAGE);
		} catch (\OCA\Notes\Service\NoteNotWritableException $e) {
			$this->logException($e);
			$response = $this->createErrorResponse($e, Http::STATUS_FORBIDDEN);
		} catch (\OCP\Lock\LockedException $e) {
			$this->logException($e);
			$response = $this->createErrorResponse($e, Http::STATUS_LOCKED);
		} catch (\Throwable $e) {
			$this->logException($e);
			$response = $this->createErrorResponse($e, Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		$response->addHeader('X-Notes-API-Versions', implode(', ', Application::$API_VERSIONS));
		return $response;
	}
}
