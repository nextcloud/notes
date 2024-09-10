<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\API;

class APIv02Test extends CommonAPITest {
	public function __construct() {
		parent::__construct('0.2', true);
	}
}
