<?php declare(strict_types=1);

namespace OCA\Notes\Migration;

use OCA\Notes\Db\MetaMapper;

use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

class Cleanup implements IRepairStep {

	private $metaMapper;

	public function __construct(MetaMapper $metaMapper) {
		$this->metaMapper = $metaMapper;
	}

	/*
	 * @inheritdoc
	 */
	public function getName() {
		return 'Clean up meta table';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$this->metaMapper->deleteAll();
	}
}
