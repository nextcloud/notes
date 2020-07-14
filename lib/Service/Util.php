<?php

declare(strict_types=1);

namespace OCA\Notes\Service;

class Util {
	public static function retryIfLocked(callable $f, int $maxRetries = 5, int $sleep = 1) {
		for ($try=1; $try <= $maxRetries; $try++) {
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
