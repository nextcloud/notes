<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class MetaMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'notes_meta');
	}

	public function getAll($userId) : array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
			);
		return $this->findEntities($qb);
	}

	public function findById(string $userId, int $fileId) : Meta {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT))
			);
		/* @phan-suppress-next-line PhanTypeMismatchReturnSuperType */
		return $this->findEntity($qb);
	}

	public function deleteAll() : void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)->executeStatement();
	}

	public function deleteByNote(int $id) : void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('file_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			)
			->executeStatement();
	}
}
