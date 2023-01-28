<?php

declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\Service\NotesService;

use OCA\Text\Event\LoadEditor;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class PageController extends Controller {
	private NotesService $notesService;
	private IUserSession $userSession;
	private IURLGenerator $urlGenerator;
	private IEventDispatcher $eventDispatcher;

	public function __construct(
		string $AppName,
		IRequest $request,
		NotesService $notesService,
		IUserSession $userSession,
		IURLGenerator $urlGenerator,
		IEventDispatcher $eventDispatcher
	) {
		parent::__construct($AppName, $request);
		$this->notesService = $notesService;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->eventDispatcher = $eventDispatcher;
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() : TemplateResponse {
		$devMode = !is_file(dirname(__FILE__).'/../../js/notes-main.js');
		$response = new TemplateResponse(
			$this->appName,
			$devMode ? 'dev-mode' : 'main',
			[ ]
		);

		if (class_exists(LoadEditor::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadEditor());
		}

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedImageDomain('*');
		$response->setContentSecurityPolicy($csp);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function create() : RedirectResponse {
		$note = $this->notesService->create($this->userSession->getUser()->getUID(), '', '');
		$note->setContent('');
		$url = $this->urlGenerator->linkToRoute('notes.page.indexnote', [ 'id' => $note->getId() ]);
		return new RedirectResponse($url . '?new');
	}
}
