<?php

namespace OCP\Share\Events {

	use OCP\Share\IShare;

	abstract class BeforeShareCreatedEvent extends \OCP\EventDispatcher\Event {
		abstract public function getShare(): IShare;
	}
}
