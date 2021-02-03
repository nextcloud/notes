<?php

declare(strict_types=1);

namespace OCA\Notes\Tests\API;

abstract class CommonAPITest extends AbstractAPITest {
	private $requiredAttributes = [
		'id' => 'integer',
		'content' => 'string',
		'title' => 'string',
		'category' => 'string',
		'modified' => 'integer',
		'favorite' => 'boolean',
	];

	private $autotitle;

	public function __construct(string $apiVersion, bool $autotitle) {
		parent::__construct($apiVersion);
		$this->autotitle = $autotitle;
	}

	public function testCheckForReferenceNotes() : array {
		$response = $this->http->request('GET', 'notes');
		$this->checkResponse($response, 'Get existing notes', 200);
		$notes = json_decode($response->getBody()->getContents());
		$this->assertNotEmpty($notes, 'List of notes');
		foreach ($notes as $note) {
			foreach ($this->requiredAttributes as $key => $type) {
				$this->assertObjectHasAttribute($key, $note, 'Note has property '.$key);
				$this->assertEquals($type, gettype($note->$key), 'Property type of '.$key);
			}
		}
		return $notes;
	}

	/** @depends testCheckForReferenceNotes */
	public function testGetNotesWithExclude(array $refNotes) : void {
		$this->checkGetReferenceNotes(
			$refNotes,
			'exclude content',
			'?exclude=content',
			false,
			['content']
		);
		$this->checkGetReferenceNotes(
			$refNotes,
			'exclude content and category',
			'?exclude=content,category',
			false,
			['content','category']
		);
	}

	/** @depends testCheckForReferenceNotes */
	public function testGetNotesWithEtag(array $refNotes) : void {
		$response1 = $this->http->request('GET', 'notes');
		$this->checkResponse($response1, 'Initial response', 200);
		$this->assertTrue($response1->hasHeader('ETag'), 'Initial response has ETag header');
		$etag = $response1->getHeaderLine('ETag');
		$this->assertRegExp('/^"[[:alnum:]]{32}"$/', $etag, 'ETag format');

		// Test If-None-Match with ETag
		$response2 = $this->http->request('GET', 'notes', [ 'headers' => [ 'If-None-Match' => $etag ] ]);
		$this->checkResponse($response2, 'ETag response', 304);
		$this->assertEquals('', $response2->getBody(), 'ETag response body');
	}

	/** @depends testCheckForReferenceNotes */
	public function testGetNotesWithPruneBefore(array $refNotes) : void {
		sleep(1); // wait for 'Last-Modified' to be >= Last-change + 1
		$response1 = $this->http->request('GET', 'notes');
		$this->checkResponse($response1, 'Initial response', 200);
		$this->assertTrue($response1->hasHeader('Last-Modified'), 'Initial response has Last-Modified header');
		$lastModified = $response1->getHeaderLine('Last-Modified');
		$dt = \DateTime::createFromFormat(\DateTime::RFC2822, $lastModified);
		$this->assertInstanceOf(\DateTime::class, $dt);

		$this->checkGetReferenceNotes(
			$refNotes,
			'pruneBefore with Last-Modified',
			'?pruneBefore='.$dt->getTimestamp(),
			true
		);
		$this->checkGetReferenceNotes(
			$refNotes,
			'pruneBefore with 1',
			'?pruneBefore=1',
			false
		);
		$this->checkGetReferenceNotes(
			$refNotes,
			'pruneBefore with PHP_INT_MAX (32bit)',
			'?pruneBefore=2147483647', // 2038-01-19 03:14:07
			true
		);
		$this->checkGetReferenceNotes(
			$refNotes,
			'pruneBefore with PHP_INT_MAX (64bit)',
			'?pruneBefore=9223372036854775807',
			true
		);
	}

	/** @depends testCheckForReferenceNotes */
	public function testCreateNotes(array $refNotes) : array {
		$this->checkGetReferenceNotes($refNotes, 'Pre-condition');
		$testNotes = [];
		$testNotes[] = $this->createNote((object)[
			'title' => 'First *manual* title',
			'content' => '# *First* test/ note'.PHP_EOL.'This is some body content with some data.',
			'favorite' => true,
			'category' => 'Test/../New Category',
			'modified' => mktime(8, 14, 30, 10, 2, 2020),
		], (object)[
			'title' => $this->autotitle ? 'First test note' : 'First manual title',
			'category' => 'Test/New Category',
		]);
		$testNotes[] = $this->createNote((object)[
			'content' => 'Note with Defaults'.PHP_EOL.'This is some body content with some data.',
		], (object)[
			'title' => $this->autotitle ? 'Note with Defaults' : 'New note', // waring: requires lang=C
			'favorite' => false,
			'category' => '',
			'modified' => time(),
		]);
		$this->checkGetReferenceNotes(array_merge($refNotes, $testNotes), 'After creating notes');
		return $testNotes;
	}

	/**
	 * @depends testCheckForReferenceNotes
	 * @depends testCreateNotes
	 */
	public function testGetSingleNote(array $refNotes, array $testNotes) : void {
		foreach ($testNotes as $testNote) {
			$response = $this->http->request('GET', 'notes/'.$testNote->id);
			$this->checkResponse($response, 'Get note '.$testNote->title, 200);
			$note = json_decode($response->getBody()->getContents());
			$this->checkReferenceNote($testNote, $note, 'Get single note');
		}
		// test non-existing note
		$response = $this->http->request('GET', 'notes/1');
		$this->assertEquals(404, $response->getStatusCode());
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
			'Get notes with category '.$category,
			'?category='.urlencode($category)
		);
	}

	/**
	 * @depends testCheckForReferenceNotes
	 * @depends testCreateNotes
	 */
	public function testUpdateNotes(array $refNotes, array $testNotes) : array {
		$this->checkGetReferenceNotes(array_merge($refNotes, $testNotes), 'Pre-condition');
		$note = $testNotes[0];
		// test update note with all attributes
		$this->updateNote($note, (object)[
			'title' => 'First *manual* edited title',
			'content' => '# *First* edited/ note'.PHP_EOL.'This is some body content with some data.',
			'favorite' => false,
			'category' => 'Test/Another Category',
			'modified' => mktime(11, 46, 23, 4, 3, 2020),
		], (object)[
			'title' => $this->autotitle ? 'First edited note' : 'First manual edited title',
		]);
		// test update note with single attributes
		$this->updateNote($note, (object)[
			'category' => 'Test/Third Category',
		], (object)[]);
		// TODO test update category with read-only folder (target category)
		$this->updateNote($note, (object)[
			'favorite' => true,
		], (object)[]);
		$this->updateNote($note, (object)[
			'content' => '# First multi edited note'.PHP_EOL.'This is some body content with some data.',
		], (object)[
			'title' => $this->autotitle ? 'First multi edited note' : 'First manual edited title',
			'modified' => time(),
		]);
		$this->checkGetReferenceNotes(array_merge($refNotes, $testNotes), 'After updating notes');
		return $testNotes;
	}

	/**
	 * @depends testCheckForReferenceNotes
	 * @depends testUpdateNotes
	 */
	public function testDeleteNotes(array $refNotes, array $testNotes) : void {
		$this->checkGetReferenceNotes(array_merge($refNotes, $testNotes), 'Pre-condition');
		foreach ($testNotes as $note) {
			$response = $this->http->request('DELETE', 'notes/'.$note->id);
			$this->checkResponse($response, 'Delete note '.$note->title, 200);
		}
		// test non-existing note
		$response = $this->http->request('DELETE', 'notes/1');
		$this->checkResponse($response, 'Delete non-existing note', 404);
		$this->checkGetReferenceNotes($refNotes, 'After deletion');
	}

	public function testInsuficientStorage() {
		$auth = ['quotatest', 'test'];
		// get notes must still work
		$response = $this->http->request('GET', 'notes', [ 'auth' => $auth ]);
		$this->checkResponse($response, 'Get existing notes', 200);
		$notes = json_decode($response->getBody()->getContents());
		$this->assertNotEmpty($notes, 'List of notes');
		$note = $notes[0]; // @phan-suppress-current-line PhanTypeArraySuspiciousNullable
		$request = (object)[ 'content' => 'New test content' ];
		// update will fail
		$response1 = $this->http->request('PUT', 'notes/'.$note->id, [ 'auth' => $auth, 'json' => $request]);
		$this->assertEquals(507, $response1->getStatusCode());
		// craete will fail
		$response2 = $this->http->request('POST', 'notes', [ 'auth' => $auth, 'json' => $request]);
		$this->assertEquals(507, $response2->getStatusCode());
	}

	public function testUnauthorized() {
		$auth = ['test', 'wrongpassword'];
		$response = $this->http->request('GET', 'notes', [ 'auth' => $auth ]);
		$this->checkResponse($response, 'Get existing notes', 401);
	}
}
