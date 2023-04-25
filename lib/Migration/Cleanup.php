<?php

declare(strict_types=1);

namespace OCA\Notes\Migration;

use OCA\Notes\Db\MetaMapper;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Cleanup implements IRepairStep {
	private MetaMapper $metaMapper;

	public function __construct(MetaMapper $metaMapper) {
		$this->metaMapper = $metaMapper;
	}

	/*
	 * @inheritdoc
	 */
	public function getName(): string {
		return 'Clean up meta table';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output): void {
		$this->metaMapper->deleteAll();
	}
}
