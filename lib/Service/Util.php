<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Service;

use OCP\IL10N;

use Psr\Log\LoggerInterface;

class Util {
	public IL10N $l10n;
	public LoggerInterface $logger;

	public function __construct(
		IL10N $l10n,
		LoggerInterface $logger,
	) {
		$this->l10n = $l10n;
		$this->logger = $logger;
	}

	public static function retryIfLocked(callable $f, int $maxRetries = 5, int $sleep = 1) {
		for ($try = 1; $try <= $maxRetries; $try++) {
			try {
				return $f();
			} catch (\OCP\Lock\LockedException $e) {
				if ($try >= $maxRetries) {
					throw $e;
				}
				sleep($sleep);
			}
		}
	}
}
