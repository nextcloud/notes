<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\API;

use PHPUnit\Framework\TestCase;

abstract class AbstractAPITest extends TestCase {
	protected string $apiVersion;
	protected \GuzzleHttp\Client $http;

	public function __construct(string $apiVersion) {
		parent::__construct();
		$this->apiVersion = $apiVersion;
	}

	protected function getAPIMajorVersion() {
		if ($this->apiVersion === '0.2') {
			return $this->apiVersion;
		} else {
			return intval($this->apiVersion);
		}
	}

	protected function setUp() : void {
		$v = $this->getAPIMajorVersion();
		$this->http = new \GuzzleHttp\Client([
			'base_uri' => 'http://localhost:8080/index.php/apps/notes/api/v' . $v . '/',
			'auth' => ['test', 'test'],
			'http_errors' => false,
			'headers' => ['Accept' => 'application/json'],
		]);
	}

	protected function checkResponse(
		\Psr\Http\Message\ResponseInterface $response,
		string $message,
		int $statusExp,
		string $contentTypeExp = 'application/json; charset=utf-8',
	) {
		$this->assertEquals($statusExp, $response->getStatusCode(), $message . ': Response status code');
		if ($statusExp !== 304) {
			$this->assertTrue(
				$response->hasHeader('Content-Type'),
				$message . ': Response has content-type header'
			);
			$this->assertEquals(
				$contentTypeExp,
				$response->getHeaderLine('Content-Type'),
				$message . ': Response content type'
			);
		}
		if ($statusExp !== 401 && $statusExp !== 304) {
			$this->assertTrue(
				$response->hasHeader('X-Notes-API-Versions'),
				$message . ': Response has Notes-API-Versions header'
			);
			$this->assertContains(
				$this->apiVersion,
				explode(', ', $response->getHeaderLine('X-Notes-API-Versions')),
				$message . ': Response Notes-API-Versions header'
			);
		}
	}

	protected function checkGetReferenceNotes(
		array $refNotes,
		string $message,
		?string $param = '',
		array $expectExclude = [],
		?array $expectedFullNotes = null,
	) : void {
		$messagePrefix = 'Check reference notes ' . $message;
		$response = $this->http->request('GET', 'notes' . $param);
		$this->checkResponse($response, $messagePrefix, 200);
		$notes = json_decode($response->getBody()->getContents());
		$this->checkReferenceNotes($refNotes, $notes, $messagePrefix, $expectExclude, $expectedFullNotes);
	}

	protected function checkReferenceNotes(
		array $refNotes,
		array $notes,
		string $messagePrefix,
		array $expectExclude = [],
		?array $expectedFullNotes = null,
	) : void {
		$this->assertIsArray($notes, $messagePrefix);
		$notesMap = $this->getNotesIdMap($notes, $messagePrefix);
		$this->assertEquals(count($refNotes), count($notes), $messagePrefix . ': Number of notes');
		foreach ($refNotes as $refNote) {
			$this->assertArrayHasKey(
				$refNote->id,
				$notesMap,
				$messagePrefix . ': Reference note ' . $refNote->title . ' exists'
			);
			$note = $notesMap[$refNote->id];
			if ($expectedFullNotes !== null && !in_array($refNote->id, $expectedFullNotes)) {
				$this->checkNoteEmpty($refNote, $note, $messagePrefix);
			} else {
				$this->checkReferenceNote($refNote, $note, $messagePrefix, $expectExclude);
			}
		}
	}

	protected function checkReferenceNote(
		\stdClass $refNote,
		\stdClass $note,
		string $messagePrefix,
		array $expectExclude = [],
	) : void {
		foreach ($expectExclude as $key) {
			$this->assertObjectNotHasAttribute(
				$key,
				$note,
				$messagePrefix . ': Note has not property ' . $key . ' (reference note: ' . $refNote->title . ')'
			);
		}
		foreach (get_object_vars($refNote) as $key => $val) {
			if (in_array($key, $expectExclude)) {
				continue;
			}
			$this->assertObjectHasAttribute(
				$key,
				$note,
				$messagePrefix . ': Note has property ' . $key . ' (reference note: ' . $refNote->title . ')'
			);
			if ($key === 'title') {
				// allow suffix for title (e.g. "Note title (2)")
				$this->assertStringStartsWith(
					$refNote->$key,
					$note->$key,
					$messagePrefix . ': Property ' . $key . ' (reference note: ' . $refNote->title . ')'
				);
				if (strlen($refNote->$key) !== strlen($note->$key)) {
					$this->assertMatchesRegularExpression(
						'/^ \(\d+\)$/',
						substr($note->$key, strlen($refNote->$key)),
						$messagePrefix . ': Property ' . $key . ' suffix (reference note: ' . $refNote->title . ')'
					);
				}
			} elseif ($key === 'modified') {
				// allow delta if the test runs slowly
				$this->assertEqualsWithDelta(
					$refNote->$key,
					$note->$key,
					10,
					$messagePrefix . ': Property ' . $key . ' (reference note: ' . $refNote->title . ')'
				);
			} else {
				$this->assertEquals(
					$refNote->$key,
					$note->$key,
					$messagePrefix . ': Property ' . $key . ' (reference note: ' . $refNote->title . ')'
				);
			}
		}
	}

	protected function checkNoteEmpty(
		\stdClass $refNote,
		\stdClass $note,
		string $messagePrefix,
	) : void {
		$this->assertEquals(
			1,
			count(get_object_vars($note)),
			$messagePrefix . ': Number of properties (reference note: ' . $refNote->title . ')'
		);
		$this->assertObjectHasAttribute(
			'id',
			$note,
			$messagePrefix . ': Note has property id (reference note: ' . $refNote->title . ')'
		);
		$this->assertEquals(
			$refNote->id,
			$note->id,
			$messagePrefix . ': ID of note (reference note: ' . $refNote->title . ')'
		);
	}

	protected function getNotesIdMap(array $notes, string $messagePrefix) : array {
		$map = [];
		foreach ($notes as $note) {
			$this->assertObjectHasAttribute('id', $note, $messagePrefix . ': Note has property id');
			$map[$note->id] = $note;
		}
		return $map;
	}

	protected function createNote(\stdClass $note, \stdClass $expected) : \stdClass {
		$response = $this->http->request('POST', 'notes', [ 'json' => $note ]);
		$this->checkResponse($response, 'Create note ' . $expected->title, 200);
		$responseNote = json_decode($response->getBody()->getContents());
		$note->id = $responseNote->id;
		foreach (get_object_vars($expected) as $key => $val) {
			$note->$key = $val;
		}
		$this->checkReferenceNote($note, $responseNote, 'Created note');
		return $note;
	}

	protected function updateNote(
		\stdClass &$note,
		\stdClass $request,
		\stdClass $expected,
		?string $etag = null,
		int $statusExp = 200,
	) {
		$requestOptions = [ 'json' => $request ];
		if ($etag !== null) {
			$requestOptions['headers'] = [ 'If-Match' => '"' . $etag . '"' ];
		}
		$response = $this->http->request('PUT', 'notes/' . $note->id, $requestOptions);
		$this->checkResponse($response, 'Update note ' . $note->title, $statusExp);
		if ($statusExp === 403) {
			return $note;
		}
		$responseNote = json_decode($response->getBody()->getContents());
		foreach (get_object_vars($request) as $key => $val) {
			$note->$key = $val;
		}
		foreach (get_object_vars($expected) as $key => $val) {
			$note->$key = $val;
		}
		$this->checkReferenceNote($note, $responseNote, 'Updated note');
		return $responseNote;
	}

	protected function checkObject(
		\stdClass $ref,
		\stdClass $obj,
		string $messagePrefix,
	) : void {
		foreach (get_object_vars($ref) as $key => $val) {
			$this->assertObjectHasAttribute(
				$key,
				$obj,
				$messagePrefix . ': Object has property ' . $key
			);
			$this->assertEquals(
				$ref->$key,
				$obj->$key,
				$messagePrefix . ': Property ' . $key
			);
		}
	}

	protected function updateSettings(
		\stdClass &$settings,
		\stdClass $request,
		\stdClass $expected,
		string $messagePrefix,
	) {
		$response = $this->http->request('PUT', 'settings', [ 'json' => $request ]);
		$this->checkResponse($response, $messagePrefix, 200);
		$responseSettings = json_decode($response->getBody()->getContents());
		foreach (get_object_vars($request) as $key => $val) {
			$settings->$key = $val;
		}
		foreach (get_object_vars($expected) as $key => $val) {
			$settings->$key = $val;
		}
		$this->checkObject($settings, $responseSettings, $messagePrefix);
	}
}
