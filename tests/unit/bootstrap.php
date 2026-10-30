<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace {
	require_once __DIR__ . '/../../vendor/autoload.php';
}

namespace OC\Hooks {
	if (!interface_exists(Emitter::class)) {
		/**
		 * Extended by OCP\Files\IRootFolder, which nextcloud/ocp ships without
		 * this interface. Declared here so PHPUnit can mock IRootFolder, the
		 * way tests/stubs/ocp.php fills the same gaps for Psalm.
		 */
		interface Emitter {
			public function listen(string $scope, string $method, callable $callback): void;

			public function removeListener(
				?string $scope = null,
				?string $method = null,
				?callable $callback = null,
			): void;
		}
	}
}
