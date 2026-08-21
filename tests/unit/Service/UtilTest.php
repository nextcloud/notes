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

/**
 * Every mutating controller action runs inside retryIfLocked(), because the
 * Text app and the desktop clients can hold a lock on the same file. If it
 * swallowed a lock instead of retrying, or gave up without reporting, a save
 * would be lost silently — so its retry and give-up behaviour is worth pinning.
 *
 * All tests pass sleep = 0 to keep the suite fast.
 */
class UtilTest extends TestCase {
	public function testReturnsTheResultWithoutRetryingWhenNothingIsLocked(): void {
		$calls = 0;

		$result = Util::retryIfLocked(function () use (&$calls): string {
			$calls++;
			return 'saved';
		}, 5, 0);

		self::assertSame('saved', $result);
		self::assertSame(1, $calls, 'a successful call must not be repeated');
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

		try {
			Util::retryIfLocked(function () use (&$calls): void {
				$calls++;
				throw new LockedException('/Notes/a.txt');
			}, 3, 0);
			self::fail('a permanently locked file must surface as an exception');
		} catch (LockedException) {
			// the controller turns this into 423 Locked
		}

		self::assertSame(3, $calls, 'it must try exactly maxRetries times');
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
			self::assertSame(1, $calls, 'only lock contention justifies a retry');
		}
	}

	public function testFalsyReturnValuesSurviveTheWrapper(): void {
		// the wrapper returns whatever the callable returns; '0', 0 and [] are
		// legitimate results and must not be confused with "no result"
		self::assertSame('0', Util::retryIfLocked(static fn (): string => '0', 5, 0));
		self::assertSame(0, Util::retryIfLocked(static fn (): int => 0, 5, 0));
		self::assertSame([], Util::retryIfLocked(static fn (): array => [], 5, 0));
		self::assertNull(Util::retryIfLocked(static fn () => null, 5, 0));
	}

	/**
	 * Characterization test — an edge case no caller hits today.
	 *
	 * The retry loop is `for ($try = 1; $try <= $maxRetries; ...)`, so a
	 * maxRetries of zero or less never enters the body: the callable is not
	 * invoked at all and the function returns null by falling off the end.
	 * Pinned because "returns null without doing the work" is a failure mode
	 * that would be very hard to trace back to here.
	 */
	public function testNonPositiveMaxRetriesNeverInvokesTheCallable(): void {
		$calls = 0;
		$callable = function () use (&$calls): string {
			$calls++;
			return 'saved';
		};

		self::assertNull(Util::retryIfLocked($callable, 0, 0));
		self::assertNull(Util::retryIfLocked($callable, -1, 0));
		self::assertSame(0, $calls);
	}
}
