<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\GenericEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Share\Events\BeforeShareCreatedEvent;
/** @phan-suppress-next-line PhanUnreferencedUseNormal */
use OCP\Share\IShare;

class Application extends App implements IBootstrap {
	public const APP_ID = 'notes';
	public static array $API_VERSIONS = [ '0.2', '1.3' ];

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerSearchProvider(SearchProvider::class);
		$context->registerDashboardWidget(DashboardWidget::class);
		$context->registerEventListener(
			BeforeTemplateRenderedEvent::class,
			BeforeTemplateRenderedListener::class
		);
		if (\class_exists(BeforeShareCreatedEvent::class)) {
			$context->registerEventListener(
				BeforeShareCreatedEvent::class,
				BeforeShareCreatedListener::class
			);
		} else {
			// FIXME: Remove once Nextcloud 28 is the minimum supported version
			\OCP\Server::get(IEventDispatcher::class)->addListener('OCP\Share::preShare', function ($event) {
				if (!$event instanceof GenericEvent) {
					return;
				}

				/** @var IShare $share */
				/** @phan-suppress-next-line PhanDeprecatedFunction */
				$share = $event->getSubject();

				$modernListener = \OCP\Server::get(BeforeShareCreatedListener::class);
				$modernListener->overwriteShareTarget($share);
			}, 1000);
		}
	}

	public function boot(IBootContext $context): void {
		$context->getAppContainer()->get(NotesHooks::class)->register();
	}
}
