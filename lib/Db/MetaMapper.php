<?php declare(strict_types=1);

namespace OCA\Notes\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;

class MetaMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'notes_meta');
	}

	public function getAll($userId) : array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('*PREFIX*notes_meta')
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
			);
		return $this->findEntities($qb);
	}
}
