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

/**
 * The chunk cursor is part of the public sync protocol: clients receive it in
 * the X-Notes-Chunk-Cursor header and hand it straight back on the next
 * request. Its serialised form therefore has to survive a round trip exactly,
 * and it has to reject anything it did not produce itself rather than decoding
 * a partial cursor and silently skipping notes.
 */
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
		self::assertSame(0, $cursor->timeStart->getTimestamp());
		self::assertSame(0, $cursor->noteLastUpdate);
		self::assertSame(0, $cursor->noteId);
		self::assertSame('0-0-0', $cursor->toString());
	}

	/**
	 * @return list<array{0: string}>
	 */
	public static function malformedCursors(): array {
		return [
			'empty' => [''],
			'too few parts' => ['1750000000-1749000000'],
			'too many parts' => ['1-2-3-4'],
			'trailing separator' => ['1-2-3-'],
			'leading separator' => ['-1-2-3'],
			'negative timestamp' => ['-1750000000-1749000000-4711'],
			'non numeric' => ['a-b-c'],
			'float' => ['1.5-2-3'],
			'whitespace padded' => [' 1-2-3 '],
			'sql-ish' => ["1-2-3' OR '1'='1"],
			'newline injected' => ["1-2-3\n4-5-6"],
		];
	}

	#[DataProvider('malformedCursors')]
	public function testMalformedCursorIsRejected(string $input): void {
		self::assertNull(
			ChunkCursor::fromString($input),
			'a cursor the app did not produce must not decode',
		);
	}

	public function testFromNoteTakesTheTimeStartItIsGivenAndNotTheNotesOwnTime(): void {
		// timeStart is the moment the *sync* began, not the note's mtime: it is
		// what the client sends back so the server can tell which notes changed
		// after the run started.
		$timeStart = (new \DateTime())->setTimestamp(1750000000);

		$meta = new Meta();
		$meta->setLastUpdate(1749000000);

		$note = $this->createMock(Note::class);
		$note->method('getId')->willReturn(4711);

		$cursor = ChunkCursor::fromNote($timeStart, new MetaNote($note, $meta));

		self::assertSame(1750000000, $cursor->timeStart->getTimestamp());
		self::assertSame(1749000000, $cursor->noteLastUpdate);
		self::assertSame(4711, $cursor->noteId);
		self::assertSame('1750000000-1749000000-4711', $cursor->toString());
	}

	public function testCursorFromNoteSurvivesASerialisationRoundTrip(): void {
		$meta = new Meta();
		$meta->setLastUpdate(1749000000);

		$note = $this->createMock(Note::class);
		$note->method('getId')->willReturn(4711);

		$original = ChunkCursor::fromNote(
			(new \DateTime())->setTimestamp(1750000000),
			new MetaNote($note, $meta),
		);
		$restored = ChunkCursor::fromString($original->toString());

		self::assertNotNull($restored);
		self::assertSame($original->toString(), $restored->toString());
	}
}
