<?php

declare(strict_types=1);

namespace OCA\Notes\Tests\API;

class APIv02Test extends CommonAPITest {
	public function __construct() {
		parent::__construct('0.2', true);
	}
}
