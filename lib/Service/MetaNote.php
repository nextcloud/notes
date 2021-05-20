<?php

declare(strict_types=1);

namespace OCA\Notes\Service;

use OCA\Notes\Db\Meta;

class MetaNote {
	/** @var Note */
	public $note;
	/** @var Meta */
	public $meta;

	public function __construct(Note $note, Meta $meta) {
		$this->note = $note;
		$this->meta = $meta;
	}
}
