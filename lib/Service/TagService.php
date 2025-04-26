<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Service;

use OCP\ITagManager;
use OCP\ITags;

class TagService {
	private ?ITags $tagger;
	private $cachedTags;

	public function __construct(ITagManager $tagManager) {
		$this->tagger = $tagManager->load('files');
	}

	/**
	 * @param list<int> $fileIds
	 * @return void
	 */
	public function loadTags(array $fileIds) : void {
		$this->cachedTags = $this->tagger->getTagsForObjects($fileIds);
	}

	public function isFavorite($fileId) : bool {
		$alltags = $this->cachedTags;
		if (!is_array($alltags)) {
			$alltags = $this->tagger->getTagsForObjects([$fileId]);
		}
		return array_key_exists($fileId, $alltags) && in_array(ITags::TAG_FAVORITE, $alltags[$fileId]);
	}

	public function setFavorite($fileId, $favorite) : void {
		if ($favorite) {
			$this->tagger->addToFavorites($fileId);
		} else {
			$this->tagger->removeFromFavorites($fileId);
		}
	}
}
