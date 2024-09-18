<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Controller;

use OCA\Notes\Service\MetaNote;

class ChunkCursor {
	public \DateTime $timeStart;
	public int $noteLastUpdate;
	public int $noteId;

	public static function fromString(string $str) : ?ChunkCursor {
		if (preg_match('/^(\d+)-(\d+)-(\d+)$/', $str, $matches)) {
			$cc = new static();
			$cc->timeStart = new \DateTime();
			$cc->timeStart->setTimestamp((int)$matches[1]);
			$cc->noteLastUpdate = (int)$matches[2];
			$cc->noteId = (int)$matches[3];
			return $cc;
		} else {
			return null;
		}
	}

	public static function fromNote(\DateTime $timeStart, MetaNote $m) : ChunkCursor {
		$cc = new static();
		$cc->timeStart = $timeStart;
		$cc->noteLastUpdate = (int)$m->meta->getLastUpdate();
		$cc->noteId = $m->note->getId();
		return $cc;
	}

	public function toString() : string {
		return $this->timeStart->getTimestamp() . '-' . $this->noteLastUpdate . '-' . $this->noteId;
	}
}
