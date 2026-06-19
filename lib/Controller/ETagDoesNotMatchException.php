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
	public function __construct(public Note $note) {
	}
}
