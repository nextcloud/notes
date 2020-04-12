<?php declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\MetaService;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class NotesApiController extends ApiController {

	/** @var NotesService */
	private $service;
	/** @var MetaService */
	private $metaService;
	/** @var Helper */
	private $helper;
	/** @var IUserSession */
	private $userSession;

	public function __construct(
		string $AppName,
		IRequest $request,
		NotesService $service,
		MetaService $metaService,
		Helper $helper,
		IUserSession $userSession
	) {
		parent::__construct($AppName, $request);
		$this->service = $service;
		$this->metaService = $metaService;
		$this->helper = $helper;
		$this->userSession = $userSession;
	}

	private function getUID() : string {
		return $this->userSession->getUser()->getUID();
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function index(string $exclude = '', int $pruneBefore = 0) : DataResponse {
		return $this->helper->handleErrorResponse(function () use ($exclude, $pruneBefore) {
			$exclude = explode(',', $exclude);
			$now = new \DateTime(); // this must be before loading notes if there are concurrent changes possible
			$notes = $this->service->getAll($this->getUID());
			$metas = $this->metaService->updateAll($this->getUID(), $notes);
			$notesData = array_map(function ($note) use ($metas, $pruneBefore, $exclude) {
				$lastUpdate = $metas[$note->getId()]->getLastUpdate();
				if ($pruneBefore && $lastUpdate<$pruneBefore) {
					return [ 'id' => $note->getId() ];
				} else {
					return $note->getData($exclude);
				}
			}, $notes);
			$etag = md5(json_encode($notesData));
			if ($this->request->getHeader('If-None-Match') === '"'.$etag.'"') {
				return new DataResponse([], Http::STATUS_NOT_MODIFIED);
			}
			return (new DataResponse($notesData))
				->setLastModified($now)
				->setETag($etag);
		});
	}


	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function get(int $id, string $exclude = '') : DataResponse {
		return $this->helper->handleErrorResponse(function () use ($id, $exclude) {
			$exclude = explode(',', $exclude);
			$note = $this->service->get($this->getUID(), $id);
			return $note->getData($exclude);
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
		bool $favorite = false
	) : DataResponse {
		return $this->helper->handleErrorResponse(function () use ($category, $title, $content, $modified, $favorite) {
			$note = $this->service->create($this->getUID(), $title, $category);
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
				$this->service->delete($this->getUID(), $note->getId());
				throw $e;
			}
			return $note->getData();
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
		bool $favorite = false
	) : DataResponse {
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
		?bool $favorite = null
	) : DataResponse {
		return $this->helper->handleErrorResponse(function () use (
			$id,
			$content,
			$modified,
			$title,
			$category,
			$favorite
		) {
			$note = $this->service->get($this->getUID(), $id);
			if ($content !== null) {
				$note->setContent($content);
			}
			if ($modified !== null) {
				$note->setModified($modified);
			}
			if ($title !== null) {
				$note->setTitleCategory($title, $category);
			} elseif ($category !== null) {
				$note->setCategory($category);
			}
			if ($favorite !== null) {
				$note->setFavorite($favorite);
			}
			return $note->getData();
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
		?bool $favorite = null
	) : DataResponse {
		return $this->helper->handleErrorResponse(function () use ($id, $content, $modified, $category, $favorite) {
			if ($content === null) {
				$note = $this->service->get($this->getUID(), $id);
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
	public function destroy(int $id) : DataResponse {
		return $this->helper->handleErrorResponse(function () use ($id) {
			$this->service->delete($this->getUID(), $id);
			return [];
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function fail() : DataResponse {
		return $this->helper->handleErrorResponse(function () {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		});
	}
}
