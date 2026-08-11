<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\AppInfo;

use OCA\Notes\Listener\NoteFileEventsListener;
use OCA\Notes\Reference\NoteReferenceProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\BeforeNodeTouchedEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Share\Events\BeforeShareCreatedEvent;

/** @phan-suppress-next-line PhanUnreferencedUseNormal */

class Application extends App implements IBootstrap {
	public const APP_ID = 'notes';
	public static array $API_VERSIONS = [ '0.2', '1.3', '1.4' ];

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerSearchProvider(SearchProvider::class);
		$context->registerReferenceProvider(NoteReferenceProvider::class);
		$context->registerDashboardWidget(DashboardWidget::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(BeforeShareCreatedEvent::class, BeforeShareCreatedListener::class);
		$context->registerEventListener(BeforeNodeWrittenEvent::class, NoteFileEventsListener::class);
		$context->registerEventListener(BeforeNodeTouchedEvent::class, NoteFileEventsListener::class);
		$context->registerEventListener(BeforeNodeDeletedEvent::class, NoteFileEventsListener::class);
		$context->registerEventListener(BeforeNodeRenamedEvent::class, NoteFileEventsListener::class);
	}

	public function boot(IBootContext $context): void {
		// Intentionally empty
	}
}
