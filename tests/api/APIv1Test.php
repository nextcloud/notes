<?php

declare(strict_types=1);

namespace OCA\Notes\Tests\API;

class APIv1Test extends CommonAPITest {
	public function __construct() {
		parent::__construct('1.1', false);
	}
}
