<?php

declare(strict_types=1);

namespace OCA\Notes\Tests\API;

class APIv1Test extends CommonAPITest {
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
}
