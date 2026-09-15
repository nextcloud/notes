<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Bootstrap for the server-free unit tests.
 *
 * `nextcloud/ocp` ships the public OCP API, but a few OCP interfaces extend
 * server-internal ones from the OC namespace that the package does not include.
 * PHPUnit needs those to exist before it can build a mock of the OCP interface,
 * so they are declared here — the same approach tests/stubs/ocp.php already
 * takes for Psalm.
 *
 * Keep this file as small as possible: every stub is a place where the tests
 * could drift from the real server. Anything that needs more than a bare
 * signature belongs in the integration tests under tests/api/ instead.
 */

namespace {
	require_once __DIR__ . '/../../vendor/autoload.php';
}

namespace OC\Hooks {
	if (!interface_exists(Emitter::class)) {
		/**
		 * Extended by OCP\Files\IRootFolder.
		 *
		 * @see https://github.com/nextcloud/server/blob/master/lib/private/Hooks/Emitter.php
		 */
		interface Emitter {
			public function listen(string $scope, string $method, callable $callback): void;

			public function removeListener(?string $scope = null, ?string $method = null, ?callable $callback = null): void;
		}
	}
}
