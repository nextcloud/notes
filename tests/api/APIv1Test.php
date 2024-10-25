<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\API;

class APIv1Test extends CommonAPITest {
	protected array $requiredSettings = [
		'notesPath' => 'string',
		'fileSuffix' => 'string',
	];

	public function __construct() {
		parent::__construct('1.3', false);
	}

	/** @depends testCheckForReferenceNotes */
	public function testReadOnlyNote(array $refNotes) : void {
		$readOnlyNotes = array_values(array_filter($refNotes, function ($note) {
			return $note->readonly;
		}));
		$this->assertNotEmpty($readOnlyNotes, 'List of read only notes');
		$note = clone $readOnlyNotes[0];
		unset($note->etag);
		$favorite = $note->favorite;
		// request with all attributes (unchanged) and just change favorite should succeed
		$upd = clone $note;
		$upd->favorite = !$favorite;
		$this->updateNote($note, $upd, (object)[]);
		// changing other attributes should fail
		$this->updateNote($note, (object)[ 'content' => 'New content' ], (object)[], null, 403);
		$this->updateNote($note, (object)[ 'title' => 'New title' ], (object)[], null, 403);
		$this->updateNote($note, (object)[ 'category' => 'New category' ], (object)[], null, 403);
		$this->updateNote($note, (object)[ 'modified' => 700 ], (object)[], null, 403);
		// change favorite back to origin
		$this->updateNote($note, (object)[
			'favorite' => $favorite,
		], (object)[
		]);
		// delete should fail
		$response = $this->http->request('DELETE', 'notes/' . $note->id);
		$this->checkResponse($response, 'Delete read-only note note', 403);
		// test if nothing has changed
		$this->checkGetReferenceNotes($refNotes, 'After read-only tests');
	}

	/**
	 * @depends testCheckForReferenceNotes
	 * @depends testCreateNotes
	 */
	public function testGetNotesWithCategory(array $refNotes, array $testNotes) : void {
		if ($this->getAPIMajorVersion() < 1) {
			$this->markTestSkipped('Get Notes with Category requires API v1');
		}
		$allNotes = array_merge($refNotes, $testNotes);
		$this->checkGetReferenceNotes($allNotes, 'Pre-condition');
		$note = $testNotes[0];
		$category = $note->category;
		$filteredNotes = array_filter(
			$allNotes,
			function ($note) use ($category) {
				return $category === $note->category;
			}
		);
		$this->assertNotEmpty($filteredNotes, 'Filtered notes');
		$this->checkGetReferenceNotes(
			$filteredNotes,
			'Get notes with category ' . $category,
			'?category=' . urlencode($category)
		);
	}

	protected function checkGetChunkNotes(
		array $indexedRefNotes,
		int $chunkSize,
		string $messagePrefix,
		?string $chunkCursor = null,
		array $collectedNotes = [],
	) : array {
		$requestCount = 0;
		$previousChunkCursor = null;
		do {
			$requestCount++;
			$previousChunkCursor = $chunkCursor;
			$query = '?chunkSize=' . $chunkSize;
			if ($chunkCursor) {
				$query .= '&chunkCursor=' . $chunkCursor;
			}
			$response = $this->http->request('GET', 'notes' . $query);
			$chunkCursor = $response->getHeaderLine('X-Notes-Chunk-Cursor');
			$this->checkResponse($response, $messagePrefix . 'Check response ' . $requestCount, 200);
			$notes = json_decode($response->getBody()->getContents());
			if ($chunkCursor) {
				$this->assertIsArray($notes, $messagePrefix . 'Response ' . $requestCount);
				$this->assertLessThanOrEqual(
					$chunkSize,
					count($notes),
					$messagePrefix . 'Notes of response ' . $requestCount
				);
				foreach ($notes as $note) {
					$this->assertArrayNotHasKey(
						$note->id,
						$collectedNotes,
						$messagePrefix . 'Note ID of response ' . $requestCount . ' in collectedNotes'
					);
					$this->assertArrayHasKey(
						$note->id,
						$indexedRefNotes,
						$messagePrefix . 'Note ID of response ' . $requestCount . ' in refNotes'
					);
					$this->checkReferenceNote(
						$indexedRefNotes[$note->id],
						$note,
						$messagePrefix . 'Note in response ' . $requestCount
					);
					$collectedNotes[$note->id] = $note;
				}
			} else {
				$leftIds = array_diff(array_keys($indexedRefNotes), array_keys($collectedNotes));
				$this->checkReferenceNotes(
					$indexedRefNotes,
					$notes,
					$messagePrefix . 'Notes of response ' . $requestCount,
					[],
					$leftIds
				);
			}
		} while ($chunkCursor && $requestCount < 100);
		$this->assertEmpty($chunkCursor, $messagePrefix . 'Last response Chunk Cursor');
		return [
			'previousChunkCursor' => $previousChunkCursor,
			'collectedNotes' => $collectedNotes,
		];
	}

	/**
	 * @depends testCheckForReferenceNotes
	 * @depends testCreateNotes
	 */
	public function testGetChunkedNotes(array $refNotes, array $testNotes) : void {
		sleep(1); // wait for 'Last-Modified' to be >= Last-change + 1
		$indexedRefNotes = $this->getNotesIdMap(array_merge($refNotes, $testNotes), 'RefNotes');
		$l = $this->checkGetChunkNotes($indexedRefNotes, 2, 'Test1: ');

		$note = $testNotes[0];
		$rn1 = $this->updateNote($note, (object)[
			'category' => 'ChunkedNote',
		], (object)[]);

		$collectedNotes = $l['collectedNotes'];
		$this->assertArrayHasKey($note->id, $collectedNotes, 'Updated note is not in last chunk.');
		unset($collectedNotes[$note->id]);
		$this->checkGetChunkNotes($indexedRefNotes, 2, 'Test2: ', $l['previousChunkCursor'], $collectedNotes);
	}

	public function testGetSettings() : \stdClass {
		$response = $this->http->request('GET', 'settings');
		$this->checkResponse($response, 'Get settings', 200);
		$settings = json_decode($response->getBody()->getContents());
		foreach ($this->requiredSettings as $key => $type) {
			$this->assertObjectHasAttribute($key, $settings, 'Settings has property ' . $key);
			$this->assertEquals($type, gettype($settings->$key), 'Property type of ' . $key);
		}
		return $settings;
	}

	/**
	 * @depends testCheckForReferenceNotes
	 * @depends testGetSettings
	 */
	public function testSettings(array $refNotes, \stdClass $settings) : void {
		$this->checkGetReferenceNotes($refNotes, 'Pre-condition');
		$originalPath = $settings->notesPath;
		$this->updateSettings($settings, (object)[
			'notesPath' => 'New-Test-Notes-Folder1',
			'fileSuffix' => '.md',
		], (object)[], 'Update both settings');
		$this->checkGetReferenceNotes([], 'Notes are gone after changing notes path');
		$this->updateSettings($settings, (object)[
			'notesPath' => '../../Test/./../New-Test-Notes-Folder2',
		], (object)[
			'notesPath' => 'New-Test-Notes-Folder2',
		], 'Update notesPath with path traversal check');
		$this->updateSettings($settings, (object)[
			'notesPath' => '',
		], (object)[], 'Update notesPath with root directory');
		$this->updateSettings($settings, (object)[
			'fileSuffix' => '.customextension',
		], (object)[], 'Update fileSuffix with custom value');
		$this->updateSettings($settings, (object)[
			'fileSuffix' => 'illegal value',
		], (object)[
			'fileSuffix' => '.illegalvalue',
		], 'Update fileSuffix with illegal value');
		$this->updateSettings($settings, (object)[
			'fileSuffix' => '',
		], (object)[
			'fileSuffix' => '.md',
		], 'Update fileSuffix with empty value');
		$this->updateSettings($settings, (object)[
			'notesPath' => null,
			'fileSuffix' => null,
		], (object)[
			'notesPath' => 'Notes',
			'fileSuffix' => '.md',
		], 'Update settings with default values');
		$this->updateSettings($settings, (object)[
			'notesPath' => $originalPath,
		], (object)[], 'Update notesPath to original value');
		$this->checkGetReferenceNotes($refNotes, 'Post-condition');
	}
}
