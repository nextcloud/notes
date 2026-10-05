<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit\Service;

use OCA\Notes\Service\Util;
use OCP\Lock\LockedException;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase {
	public function testReturnsTheResultWithoutRetryingWhenNothingIsLocked(): void {
		$calls = 0;

		$result = Util::retryIfLocked(function () use (&$calls): string {
			$calls++;
			return 'saved';
		}, 5, 0);

		self::assertSame('saved', $result);
		self::assertSame(1, $calls);
	}

	public function testRetriesUntilTheLockIsReleased(): void {
		$calls = 0;

		$result = Util::retryIfLocked(function () use (&$calls): string {
			$calls++;
			if ($calls < 3) {
				throw new LockedException('/Notes/a.txt');
			}
			return 'saved';
		}, 5, 0);

		self::assertSame('saved', $result);
		self::assertSame(3, $calls);
	}

	public function testRethrowsAfterTheLastAttempt(): void {
		$calls = 0;

		$this->expectException(LockedException::class);
		try {
			Util::retryIfLocked(function () use (&$calls): void {
				$calls++;
				throw new LockedException('/Notes/a.txt');
			}, 3, 0);
		} finally {
			self::assertSame(3, $calls);
		}
	}

	public function testOtherExceptionsAreNotRetried(): void {
		$calls = 0;

		$this->expectException(\RuntimeException::class);
		try {
			Util::retryIfLocked(function () use (&$calls): void {
				$calls++;
				throw new \RuntimeException('unrelated');
			}, 5, 0);
		} finally {
			self::assertSame(1, $calls);
		}
	}
}
