<?php

declare(strict_types=1);

namespace OCA\Notes\Controller;

use OCA\Notes\Service\Note;

use Exception;

class ETagDoesNotMatchException extends Exception {
	public Note $note;

	public function __construct(Note $note) {
		$this->note = $note;
	}
}
