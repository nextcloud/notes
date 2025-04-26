<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Service;

use OCA\Notes\Db\Meta;
use OCA\Notes\Db\MetaMapper;

/** MetaService.
 *
 * The MetaService maintains information about notes that cannot be gathered
 * from Nextcloud middleware.
 *
 * Background: we want to minimize the transfered data size during
 * synchronization with mobile clients. Therefore, the full note is only sent
 * to the client if it was updated since last synchronization. For this
 * purpose, we need to know at which time a file's content was changed.
 * Unfortunately, Nextcloud does not save this information.  Important: the
 * filemtime is not sufficient for this, since a file's content can be changed
 * without changing it's filemtime!
 *
 * Therefore, the Notes app maintains this information on its own. It is saved
 * in the database table `notes_meta`. To be honest, we do not store the exact
 * changed time, but a time `t` that is at some point between the real changed
 * time and the next synchronization time. However, this is totally sufficient
 * for this purpose.
 *
 * Therefore, on synchronization, the method `MetaService.getAll` is called.
 * It generates an ETag for each note and compares it with the ETag from
 * `notes_meta` database table in order to detect changes (or creates an entry
 * if not existent). If there are changes, the ETag is updated and `LastUpdate`
 * is set to the current time. The ETag is a hash over all note attributes
 * (except content, see below).
 *
 * But in order to further speed up synchronization, the content is not
 * compared every time (this would be very expensive!). Instead, a file hook
 * (see `OCA\Notes\NotesHook`) deletes the meta entry on every file change. As
 * a consequence, a new entry in `note_meta` is created on next
 * synchronization.
 *
 * Hence, instead of using the real content for generating the note's ETag, it
 * uses a "content ETag" which is a hash over the content. Additionaly to the
 * file hooks, this "content ETag" is updated if Nextcloud's "file ETag" has
 * changed (but again, the "file ETag" is just an indicator, since it is not a
 * hash over the content).
 *
 * All in all, this has some complexity, but we can speed up synchronization
 * with this approach! :-)
 */
class MetaService {
	private MetaMapper $metaMapper;
	private Util $util;

	public function __construct(MetaMapper $metaMapper, Util $util) {
		$this->metaMapper = $metaMapper;
		$this->util = $util;
	}

	public function deleteByNote(int $id) : void {
		$this->metaMapper->deleteByNote($id);
	}

	public function getAll(string $userId, array $notes, bool $forceUpdate = false) : array {
		// load data
		$metas = $this->metaMapper->getAll($userId);
		$metas = $this->getIndexedArray($metas, 'fileId');
		$result = [];

		// delete obsolete notes
		foreach ($metas as $id => $meta) {
			if (!array_key_exists($id, $notes)) {
				// DELETE obsolete notes
				$this->metaMapper->delete($meta);
			}
		}

		$insertErrorCount = 0;
		// insert/update changes
		foreach ($notes as $id => $note) {
			if (!array_key_exists($id, $metas)) {
				// INSERT new notes
				$meta = $this->createMeta($userId, $note, function () use (&$insertErrorCount) {
					$insertErrorCount++;
				});
			} else {
				// UPDATE changed notes
				$meta = $metas[$id];
				if ($this->updateIfNeeded($meta, $note, $forceUpdate)) {
					$this->metaMapper->update($meta);
				}
			}
			$result[$id] = new MetaNote($note, $meta);
		}
		if ($insertErrorCount) {
			if ($insertErrorCount == count($notes)) {
				$this->util->logger->warning(
					'Database failed inserting Meta objects for all ' . $insertErrorCount . ' notes. ' .
					'If this happens consistently, there is a problem with your database.',
				);
			} else {
				$this->util->logger->warning(
					'Database failed inserting Meta objects for ' . $insertErrorCount . ' times.',
				);
			}
		}
		return $result;
	}

	public function update(string $userId, Note $note) : Meta {
		$meta = null;
		try {
			$meta = $this->metaMapper->findById($userId, $note->getId());
		} catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
		}
		if ($meta === null) {
			$meta = $this->createMeta($userId, $note);
		} elseif ($this->updateIfNeeded($meta, $note, true)) {
			$this->metaMapper->update($meta);
		}
		return $meta;
	}

	private function getIndexedArray(array $data, string $property) : array {
		$property = ucfirst($property);
		$getter = 'get' . $property;
		$result = [];
		foreach ($data as $entity) {
			$result[$entity->$getter()] = $entity;
		}
		return $result;
	}

	private function createMeta(string $userId, Note $note, ?callable $onError = null) : Meta {
		$meta = new Meta();
		$meta->setUserId($userId);
		$meta->setFileId($note->getId());
		$meta->setLastUpdate(time());
		$this->updateIfNeeded($meta, $note, true);
		try {
			$this->metaMapper->insert($meta);
		} catch (\Throwable $e) {
			// It's likely that a concurrent request created this entry, too.
			// We can ignore this, since the result should be the same.
			// But we log it for being able to detect other problems.
			// (If this happens often, this may cause performance problems.)
			$loglevel = 'warning';
			if ($onError) {
				$onError();
				$loglevel = 'debug';
			}
			$this->util->logger->$loglevel(
				'Could not insert meta data for note ' . $note->getId(),
				[ 'exception' => $e ]
			);
		}
		return $meta;
	}

	private function updateIfNeeded(Meta &$meta, Note $note, bool $forceUpdate) : bool {
		$generateContentEtag = $forceUpdate || !$meta->getContentEtag();
		$fileEtag = $note->getFileEtag();
		// a changed File-ETag is an indicator for changed content
		if ($fileEtag !== $meta->getFileEtag()) {
			$meta->setFileEtag($fileEtag);
			$generateContentEtag = true;
		}
		// generate new Content-ETag
		if ($generateContentEtag) {
			$contentEtag = $this->generateContentEtag($note); // this is expensive
			if ($contentEtag !== $meta->getContentEtag()) {
				$meta->setContentEtag($contentEtag);
			}
		}
		// always update ETag based on meta data (not content!)
		$etag = $this->generateEtag($meta, $note);
		if ($etag !== $meta->getEtag()) {
			$meta->setEtag($etag);
			$meta->setLastUpdate(time());
		}
		return !empty($meta->getUpdatedFields());
	}

	// warning: this is expensive
	private function generateContentEtag(Note $note) : string {
		try {
			return Util::retryIfLocked(function () use ($note) {
				return md5($note->getContent());
			}, 3);
		} catch (\Throwable $t) {
			$this->util->logger->error(
				'Could not generate Content Etag for note ' . $note->getId(),
				[ 'exception' => $t ]
			);
			return '';
		}
	}

	// this is not expensive, since we use the content ETag instead of the content itself
	private function generateEtag(Meta &$meta, Note $note) : string {
		$data = [
			$note->getId(),
			$note->getTitle(),
			$note->getModified(),
			$note->getCategory(),
			$note->getFavorite(),
			$note->getReadOnly(),
			$meta->getContentEtag(),
		];
		return md5(json_encode($data));
	}
}
