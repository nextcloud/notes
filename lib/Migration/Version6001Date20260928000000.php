<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Migration;

use OCP\Migration\BigIntMigration;

class Version6001Date20260928000000 extends BigIntMigration {
	protected function getColumnsByTable(): array {
		return [
			'notes_meta' => ['id', 'file_id', 'last_update'],
		];
	}
}
