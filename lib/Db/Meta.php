<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class Meta
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method integer getFileId()
 * @method void setFileId(integer $value)
 * @method integer getLastUpdate()
 * @method void setLastUpdate(integer $value)
 * @method string getEtag()
 * @method void setEtag(string $value)
 * @method string getContentEtag()
 * @method void setContentEtag(string $value)
 * @method string getFileEtag()
 * @method void setFileEtag(string $value)
 * @package OCA\Notes\Db
 */
class Meta extends Entity {
	protected $userId;
	protected $fileId;
	protected $lastUpdate;
	protected $etag;
	protected $contentEtag;
	protected $fileEtag;
}
