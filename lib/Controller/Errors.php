<?php

namespace OCA\Notes\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Notes\Service\NoteDoesNotExistException;

/**
 * Class Errors
 *
 * @package OCA\Notes\Controller
 */
trait Errors {
	/**
	 * @param $callback
	 * @return DataResponse
	 */
	protected function respond($callback) {
		try {
			return new DataResponse($callback());
		} catch (NoteDoesNotExistException $ex) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
	}
}
