<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Service;

use OCA\Notes\Db\Meta;

class MetaNote {
	public Note $note;
	public Meta $meta;

	public function __construct(Note $note, Meta $meta) {
		$this->note = $note;
		$this->meta = $meta;
	}
}
