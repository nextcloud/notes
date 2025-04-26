<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013 Bernhard Posselt <nukeawhale@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Controller;

use OCA\Files\Event\LoadSidebar;
use OCA\Notes\AppInfo\Application;
use OCA\Notes\Service\NotesService;

use OCA\Notes\Service\SettingsService;
use OCA\Text\Event\LoadEditor;
use OCA\Viewer\Event\LoadViewer;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class PageController extends Controller {
	private NotesService $notesService;
	private IConfig $config;
	private IUserSession $userSession;
	private IURLGenerator $urlGenerator;
	private IEventDispatcher $eventDispatcher;
	private IInitialState $initialState;

	public function __construct(
		string $AppName,
		IRequest $request,
		NotesService $notesService,
		IConfig $config,
		IUserSession $userSession,
		IURLGenerator $urlGenerator,
		IEventDispatcher $eventDispatcher,
		IInitialState $initialState,
	) {
		parent::__construct($AppName, $request);
		$this->notesService = $notesService;
		$this->config = $config;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->eventDispatcher = $eventDispatcher;
		$this->initialState = $initialState;
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @suppress PhanUndeclaredClassReference, PhanTypeMismatchArgument, PhanUndeclaredClassMethod
	 */
	public function index() : TemplateResponse {
		$devMode = !is_file(dirname(__FILE__) . '/../../js/notes-main.js');
		$response = new TemplateResponse(
			$this->appName,
			$devMode ? 'dev-mode' : 'main',
			[ ]
		);

		if (\OCP\Server::get(IAppManager::class)->isEnabledForUser('text') && class_exists(LoadEditor::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadEditor());
		}

		if (class_exists(LoadSidebar::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadSidebar());
		}

		if (\OCP\Server::get(IAppManager::class)->isEnabledForUser('viewer') && class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}

		$this->initialState->provideInitialState(
			'config',
			(array)\OCP\Server::get(SettingsService::class)->getPublic($this->userSession->getUser()->getUID())
		);

		$this->initialState->provideInitialState(
			'editorHint',
			$this->config->getUserValue($this->userSession->getUser()->getUID(), Application::APP_ID, 'editorHint', '')
		);

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
