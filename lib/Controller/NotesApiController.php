<?php

namespace OCA\Notes\Controller;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\MetaService;
use OCA\Notes\Service\InsufficientStorageException;
use OCA\Notes\Service\NoteDoesNotExistException;
use OCA\Notes\Db\Note;

/**
 * Class NotesApiController
 *
 * @package OCA\Notes\Controller
 */
class NotesApiController extends ApiController {

	use Errors;

	/** @var NotesService */
	private $service;
	/** @var MetaService */
	private $metaService;
	/** @var IUserSession */
	private $userSession;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param NotesService $service
	 * @param IUserSession $userSession
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		NotesService $service,
		MetaService $metaService,
		IUserSession $userSession
	) {
		parent::__construct($AppName, $request);
		$this->service = $service;
		$this->metaService = $metaService;
		$this->userSession = $userSession;
	}

	private function getUID() {
		return $this->userSession->getUser()->getUID();
	}

	/**
	 * @param Note $note
	 * @param string[] $exclude the fields that should be removed from the
	 * notes
	 * @return Note
	 */
	private function excludeFields(Note &$note, array $exclude) {
		if (count($exclude) > 0) {
			foreach ($exclude as $field) {
				if (property_exists($note, $field)) {
					unset($note->$field);
				}
			}
		}
		return $note;
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param string $exclude
	 * @return DataResponse
	 */
	public function index($exclude = '', $pruneBefore = 0) {
		$exclude = explode(',', $exclude);
		$now = new \DateTime(); // this must be before loading notes if there are concurrent changes possible
		$notes = $this->service->getAll($this->getUID());
		$metas = $this->metaService->updateAll($this->getUID(), $notes);
		foreach ($notes as $note) {
			$lastUpdate = $metas[$note->getId()]->getLastUpdate();
			if ($pruneBefore && $lastUpdate<$pruneBefore) {
				$vars = get_object_vars($note);
				unset($vars['id']);
				$this->excludeFields($note, array_keys($vars));
			} else {
				$this->excludeFields($note, $exclude);
			}
		}
		$etag = md5(json_encode($notes));
		if ($this->request->getHeader('If-None-Match') === '"'.$etag.'"') {
			return new DataResponse([], Http::STATUS_NOT_MODIFIED);
		}
		return (new DataResponse($notes))
			->setLastModified($now)
			->setETag($etag);
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @param string $exclude
	 * @return DataResponse
	 */
	public function get($id, $exclude = '') {
		try {
			$exclude = explode(',', $exclude);
			$note = $this->service->get($id, $this->getUID());
			$note = $this->excludeFields($note, $exclude);
			return new DataResponse($note);
		} catch (NoteDoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param string $content
	 * @param string $category
	 * @param int $modified
	 * @param boolean $favorite
	 * @return DataResponse
	 */
	public function create($content, $category = null, $modified = 0, $favorite = null) {
		try {
			$note = $this->service->create($this->getUID());
			try {
				$note = $this->updateData($note->getId(), $content, $category, $modified, $favorite);
			} catch (\Throwable $e) {
				// roll-back note creation
				$this->service->delete($note->getId(), $this->getUID());
				throw $e;
			}
			return new DataResponse($note);
		} catch (InsufficientStorageException $e) {
			return new DataResponse([], Http::STATUS_INSUFFICIENT_STORAGE);
		}
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @param string $content
	 * @param string $category
	 * @param int $modified
	 * @param boolean $favorite
	 * @return DataResponse
	 */
	public function update($id, $content = null, $category = null, $modified = 0, $favorite = null) {
		try {
			$note = $this->updateData($id, $content, $category, $modified, $favorite);
			return new DataResponse($note);
		} catch (NoteDoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (InsufficientStorageException $e) {
			return new DataResponse([], Http::STATUS_INSUFFICIENT_STORAGE);
		}
	}

	/**
	 * Updates a note, used by create and update
	 * @param int $id
	 * @param string|null $content
	 * @param int $modified
	 * @param boolean|null $favorite
	 * @return Note
	 */
	private function updateData($id, $content, $category, $modified, $favorite) {
		if ($favorite!==null) {
			$this->service->favorite($id, $favorite, $this->getUID());
		}
		if ($content===null) {
			return $this->service->get($id, $this->getUID());
		} else {
			return $this->service->update($id, $content, $this->getUID(), $category, $modified);
		}
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function destroy($id) {
		try {
			$this->service->delete($id, $this->getUID());
			return new DataResponse([]);
		} catch (NoteDoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
	}
}
