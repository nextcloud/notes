<?php

declare(strict_types=1);

namespace OCA\Notes\Controller;

use Exception;

use OCA\Notes\Service\Note;

class ETagDoesNotMatchException extends Exception {
	public Note $note;

	public function __construct(Note $note) {
		$this->note = $note;
	}
}
