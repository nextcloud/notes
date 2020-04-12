<?php declare(strict_types=1);

namespace OCA\Notes\Service;

use OCA\Notes\Db\Meta;
use OCA\Notes\Db\MetaMapper;

class MetaService {

	private $metaMapper;

	public function __construct(MetaMapper $metaMapper) {
		$this->metaMapper = $metaMapper;
	}

	public function updateAll(string $userId, array $notes) : array {
		$metas = $this->metaMapper->getAll($userId);
		$metas = $this->getIndexedArray($metas, 'fileId');
		$notes = $this->getIndexedArray($notes, 'id');
		foreach ($metas as $id => $meta) {
			if (!array_key_exists($id, $notes)) {
				// DELETE obsolete notes
				$this->metaMapper->delete($meta);
				unset($metas[$id]);
			}
		}
		foreach ($notes as $id => $note) {
			if (!array_key_exists($id, $metas)) {
				// INSERT new notes
				$metas[$note->getId()] = $this->create($userId, $note);
			} elseif ($note->getEtag()!==$metas[$id]->getEtag()) {
				// UPDATE changed notes
				$meta = $metas[$id];
				$this->updateIfNeeded($meta, $note);
			}
		}
		return $metas;
	}

	private function getIndexedArray(array $data, string $property) : array {
		$property = ucfirst($property);
		$getter = 'get'.$property;
		$result = array();
		foreach ($data as $entity) {
			$result[$entity->$getter()] = $entity;
		}
		return $result;
	}

	private function create(string $userId, Note $note) : Meta {
		$meta = Meta::fromNote($note, $userId);
		$this->metaMapper->insert($meta);
		return $meta;
	}

	private function updateIfNeeded(Meta &$meta, Note $note) : void {
		if ($note->getEtag()!==$meta->getEtag()) {
			$meta->setEtag($note->getEtag());
			$meta->setLastUpdate(time());
			$this->metaMapper->update($meta);
		}
	}
}
