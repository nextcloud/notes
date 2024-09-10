<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3005Date20200528204431 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable('notes_meta');
		$table->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$table->addColumn('file_id', 'integer', [
			'notnull' => true,
		]);
		$table->addColumn('user_id', 'string', [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('last_update', 'integer', [
			'notnull' => true,
		]);
		$table->addColumn('etag', 'string', [
			'notnull' => true,
			'length' => 32,
		]);
		$table->addColumn('content_etag', 'string', [
			'notnull' => true,
			'length' => 32,
		]);
		$table->addColumn('file_etag', 'string', [
			'notnull' => true,
			'length' => 40,
		]);
		$table->setPrimaryKey(['id']);
		$table->addIndex(['file_id'], 'notes_meta_file_id_index');
		$table->addIndex(['user_id'], 'notes_meta_user_id_index');
		$table->addUniqueIndex(['file_id', 'user_id'], 'notes_meta_file_user_index');

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}
}
