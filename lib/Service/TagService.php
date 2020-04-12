<?php declare(strict_types=1);

namespace OCA\Notes\Service;

use OCP\ITagManager;

class TagService {
	private $tagger;
	private $cachedTags;

	public function __construct(ITagManager $tagManager) {
		$this->tagger = $tagManager->load('files');
	}

	public function loadTags(array $fileIds) : void {
		$this->cachedTags = $this->tagger->getTagsForObjects($fileIds);
	}

	// TODO NC19: replace this by OCP\ITags::TAG_FAVORITE
	// OCP\ITags::TAG_FAVORITE was introduced in NC19
	// https://github.com/nextcloud/server/pull/19412
	/**
	 * @suppress PhanUndeclaredClassConstant
	 * @suppress PhanUndeclaredConstant
	 * @suppress PhanUndeclaredConstantOfClass
	 */
	private static function getTagFavorite() {
		if (defined('OCP\ITags::TAG_FAVORITE')) {
			return \OCP\ITags::TAG_FAVORITE;
		} else {
			return \OC\Tags::TAG_FAVORITE;
		}
	}

	public function isFavorite($fileId) : bool {
		$alltags = $this->cachedTags;
		if (!is_array($alltags)) {
			$alltags = $this->tagger->getTagsForObjects([$fileId]);
		}
		return array_key_exists($fileId, $alltags) && in_array(self::getTagFavorite(), $alltags[$fileId]);
	}

	public function setFavorite($fileId, $favorite) : void {
		if ($favorite) {
			$this->tagger->addToFavorites($fileId);
		} else {
			$this->tagger->removeFromFavorites($fileId);
		}
	}
}
