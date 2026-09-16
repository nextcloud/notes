<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\Unit\Controller;

use OCA\Notes\Controller\ChunkCursor;
use OCA\Notes\Db\Meta;
use OCA\Notes\Service\MetaNote;
use OCA\Notes\Service\Note;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChunkCursorTest extends TestCase {
	public function testRoundTripPreservesAllThreeFields(): void {
		$cursor = ChunkCursor::fromString('1750000000-1749000000-4711');

		self::assertNotNull($cursor);
		self::assertSame(1750000000, $cursor->timeStart->getTimestamp());
		self::assertSame(1749000000, $cursor->noteLastUpdate);
		self::assertSame(4711, $cursor->noteId);
		self::assertSame('1750000000-1749000000-4711', $cursor->toString());
	}

	public function testZeroesAreAValidCursor(): void {
		$cursor = ChunkCursor::fromString('0-0-0');

		self::assertNotNull($cursor);
		self::assertSame('0-0-0', $cursor->toString());
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function malformedCursors(): array {
		return [
			'empty' => [''],
			'too few parts' => ['1750000000-1749000000'],
			'too many parts' => ['1-2-3-4'],
			'leading separator' => ['-1-2-3'],
			'non numeric' => ['a-b-c'],
			'float' => ['1.5-2-3'],
			'whitespace padded' => [' 1-2-3 '],
			'newline injected' => ["1-2-3\n4-5-6"],
		];
	}

	#[DataProvider('malformedCursors')]
	public function testMalformedCursorIsRejected(string $input): void {
		self::assertNull(ChunkCursor::fromString($input));
	}

	public function testFromNoteUsesTheGivenSyncStartAndNotTheNotesOwnTime(): void {
		$meta = new Meta();
		$meta->setLastUpdate(1749000000);

		$note = $this->createMock(Note::class);
		$note->method('getId')->willReturn(4711);

		$cursor = ChunkCursor::fromNote(
			(new \DateTime())->setTimestamp(1750000000),
			new MetaNote($note, $meta),
		);

		self::assertSame('1750000000-1749000000-4711', $cursor->toString());
	}
}
