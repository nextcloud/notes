<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Controller;

use Exception;

use OCA\Notes\Service\Note;

class ETagDoesNotMatchException extends Exception {
	public Note $note;

	public function __construct(Note $note) {
		$this->note = $note;
	}
}
