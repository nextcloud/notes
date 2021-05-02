<?php

declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\AppInfo\Application;
use OCA\Notes\Db\Meta;
use OCA\Notes\Service\Note;
use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\MetaService;
use OCA\Notes\Service\Util;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

use Psr\Log\LoggerInterface;

class Helper {

	/** @var NotesService */
	private $notesService;
	/** @var MetaService */
	private $metaService;
	/** @var LoggerInterface */
	public $logger;
	/** @var IUserSession */
	private $userSession;

	public function __construct(
		NotesService $notesService,
		MetaService $metaService,
		IUserSession $userSession,
		LoggerInterface $logger
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
			if (!in_array('"'.$meta->getEtag().'"', $matchEtags)) {
				throw new ETagDoesNotMatchException($note);
			}
		}
		return $note;
	}

	public function getNoteData(Note $note, array $exclude = [], Meta $meta = null) : array {
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
		string $category = null
	) : array {
		$userId = $this->getUID();
		$data = $this->notesService->getAll($userId);
		$notes = $data['notes'];
		$metas = $this->metaService->updateAll($userId, $notes);
		if ($category !== null) {
			$notes = array_values(array_filter($notes, function ($note) use ($category) {
				return $note->getCategory() === $category;
			}));
		}
		$notesData = array_map(function ($note) use ($metas, $pruneBefore, $exclude) {
			$meta = $metas[$note->getId()];
			if ($pruneBefore && $meta->getLastUpdate() < $pruneBefore) {
				return [ 'id' => $note->getId() ];
			} else {
				return $this->getNoteData($note, $exclude, $meta);
			}
		}, $notes);
		return [
			'notes' => $notesData,
			'categories' => $data['categories'],
		];
	}

	public function logException(\Throwable $e) : void {
		$this->logger->error('Controller failed with '.get_class($e), [ 'exception' => $e ]);
	}

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
