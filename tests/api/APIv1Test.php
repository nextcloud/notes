<?php

declare(strict_types=1);

namespace OCA\Notes\Tests\API;

class APIv1Test extends CommonAPITest {
	protected $requiredSettings = [
		'notesPath' => 'string',
		'fileSuffix' => 'string',
	];

	public function __construct() {
		parent::__construct('1.2', false);
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
		$response = $this->http->request('DELETE', 'notes/'.$note->id);
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
			'Get notes with category '.$category,
			'?category='.urlencode($category)
		);
	}

	public function testGetSettings() : \stdClass {
		$response = $this->http->request('GET', 'settings');
		$this->checkResponse($response, 'Get settings', 200);
		$settings = json_decode($response->getBody()->getContents());
		foreach ($this->requiredSettings as $key => $type) {
			$this->assertObjectHasAttribute($key, $settings, 'Settings has property '.$key);
			$this->assertEquals($type, gettype($settings->$key), 'Property type of '.$key);
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
			'fileSuffix' => 'illegal value',
		], (object)[
			'fileSuffix' => '.txt',
		], 'Update fileSuffix with illegal value');
		$this->updateSettings($settings, (object)[
			'notesPath' => null,
			'fileSuffix' => null,
		], (object)[
			'notesPath' => 'Notes',
			'fileSuffix' => '.txt',
		], 'Update settings with default values');
		$this->updateSettings($settings, (object)[
			'notesPath' => $originalPath,
		], (object)[], 'Update notesPath to original value');
		$this->checkGetReferenceNotes($refNotes, 'Post-condition');
	}
}
