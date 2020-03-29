<?php declare(strict_types=1);

namespace OCA\Notes\Tests\API;

use PHPUnit\Framework\TestCase;

abstract class AbstractAPITest extends TestCase {
	protected $apiVersion;
	protected $http;

	public function __construct(string $apiVersion) {
		parent::__construct();
		$this->apiVersion = $apiVersion;
	}

	protected function setUp() : void {
		$this->http = new \GuzzleHttp\Client([
			'base_uri' => 'http://localhost:8080/index.php/apps/notes/api/'.$this->apiVersion.'/',
			'auth' => ['test', 'test'],
			'http_errors' => false,
		]);
	}

	protected function checkResponse(
		\Psr\Http\Message\ResponseInterface $response,
		string $message,
		int $statusExp,
		string $contentTypeExp = 'application/json; charset=utf-8'
	) {
		$this->assertEquals($statusExp, $response->getStatusCode(), $message.': Response status code');
		$this->assertTrue($response->hasHeader('Content-Type'), $message.': Response has content-type header');
		$this->assertEquals(
			$contentTypeExp,
			$response->getHeaderLine('Content-Type'),
			$message.': Response content type'
		);
	}

	protected function checkGetReferenceNotes(
		array $refNotes,
		string $message,
		?string $param = '',
		bool $expectEmpty = false,
		array $expectExclude = []
	) : void {
		$messagePrefix = 'Check reference notes '.$message;
		$response = $this->http->request('GET', 'notes' . $param);
		$this->checkResponse($response, $messagePrefix, 200);
		$notes = json_decode($response->getBody()->getContents());
		$notesMap = self::getNotesIdMap($notes);
		$this->assertEquals(count($refNotes), count($notes), $messagePrefix.': Number of notes');
		foreach ($refNotes as $refNote) {
			$this->assertArrayHasKey(
				$refNote->id,
				$notesMap,
				$messagePrefix.': Reference note '.$refNote->title.' exists'
			);
			$note = $notesMap[$refNote->id];
			if ($expectEmpty) {
				$this->checkNoteEmpty($refNote, $note, $messagePrefix);
			} else {
				$this->checkReferenceNote($refNote, $note, $messagePrefix, $expectExclude);
			}
		}
	}

	protected function checkReferenceNote(
		object $refNote,
		object $note,
		string $messagePrefix,
		array $expectExclude = []
	) : void {
		foreach ($expectExclude as $key) {
			$this->assertObjectNotHasAttribute(
				$key,
				$note,
				$messagePrefix.': Note has not property '.$key.' (reference note: '.$refNote->title.')'
			);
		}
		foreach (get_object_vars($refNote) as $key => $val) {
			if (in_array($key, $expectExclude)) {
				continue;
			}
			$this->assertObjectHasAttribute(
				$key,
				$note,
				$messagePrefix.': Note has property '.$key.' (reference note: '.$refNote->title.')'
			);
			if ($key==='title') {
				// allow suffix for title (e.g. "Note title (2)")
				$this->assertStringStartsWith(
					$refNote->$key,
					$note->$key,
					$messagePrefix.': Property '.$key.' (reference note: '.$refNote->title.')'
				);
				if (strlen($refNote->$key) !== strlen($note->$key)) {
					$this->assertRegExp(
						'/^ \(\d+\)$/',
						substr($note->$key, strlen($refNote->$key)),
						$messagePrefix.': Property '.$key.' suffix (reference note: '.$refNote->title.')'
					);
				}
			} elseif ($key==='modified') {
				// allow delta if the test runs slowly
				$this->assertEqualsWithDelta(
					$refNote->$key,
					$note->$key,
					5,
					$messagePrefix.': Property '.$key.' (reference note: '.$refNote->title.')'
				);
			} else {
				$this->assertEquals(
					$refNote->$key,
					$note->$key,
					$messagePrefix.': Property '.$key.' (reference note: '.$refNote->title.')'
				);
			}
		}
	}

	protected function checkNoteEmpty(
		object $refNote,
		object $note,
		string $messagePrefix
	) : void {
		$this->assertEquals(
			1,
			count(get_object_vars($note)),
			$messagePrefix.': Number of properties (reference note: '.$refNote->title.')'
		);
		$this->assertObjectHasAttribute(
			'id',
			$note,
			$messagePrefix.': Note has property id (reference note: '.$refNote->title.')'
		);
		$this->assertEquals(
			$refNote->id,
			$note->id,
			$messagePrefix.': ID of note (reference note: '.$refNote->title.')'
		);
	}

	protected static function getNotesIdMap(array $notes) : array {
		$map = [];
		foreach ($notes as $note) {
			$map[$note->id] = $note;
		}
		return $map;
	}

	protected function createNote(\stdClass $note, \stdClass $expected) : \stdClass {
		$response = $this->http->request('POST', 'notes', [ 'json' => $note ]);
		$this->checkResponse($response, 'Create note '.$expected->title, 200);
		$responseNote = json_decode($response->getBody()->getContents());
		$note->id = $responseNote->id;
		foreach (get_object_vars($expected) as $key => $val) {
			$note->$key = $val;
		}
		$this->checkReferenceNote($note, $responseNote, 'Created note');
		return $note;
	}

	protected function updateNote(\stdClass &$note, \stdClass $request, \stdClass $expected) {
		$response = $this->http->request('PUT', 'notes/'.$note->id, [ 'json' => $request ]);
		$this->checkResponse($response, 'Update note '.$note->title, 200);
		$responseNote = json_decode($response->getBody()->getContents());
		foreach (get_object_vars($request) as $key => $val) {
			$note->$key = $val;
		}
		foreach (get_object_vars($expected) as $key => $val) {
			$note->$key = $val;
		}
		$this->checkReferenceNote($note, $responseNote, 'Updated note');
	}
}
