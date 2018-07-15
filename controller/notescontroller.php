<?php
/**
 * Nextcloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2012, 2014
 */

namespace OCA\Notes\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\DataResponse;

use OCA\Notes\Service\NotesService;

/**
 * Class NotesController
 *
 * @package OCA\Notes\Controller
 */
class NotesController extends Controller {

    use Errors;

    /** @var NotesService */
    private $notesService;
    /** @var IConfig */
    private $settings;
    /** @var string */
    private $userId;

    /**
     * @param string $AppName
     * @param IRequest $request
     * @param NotesService $service
     * @param IConfig $settings
     * @param string $UserId
     */
    public function __construct($AppName, IRequest $request,
                                NotesService $service, IConfig $settings,
                                $UserId){
        parent::__construct($AppName, $request);
        $this->notesService = $service;
        $this->settings = $settings;
        $this->userId = $UserId;
    }


    /**
     * @NoAdminRequired
     */
    public function index() {
        return new DataResponse($this->notesService->getAll($this->userId, true));
    }


    /**
     * @NoAdminRequired
     *
     * @param int $id
     * @return DataResponse
     */
    public function get($id) {
        // save the last viewed note
        $this->settings->setUserValue(
            $this->userId, $this->appName, 'notesLastViewedNote', $id
        );

        return $this->respond(function ()  use ($id) {
            return $this->notesService->get($id, $this->userId);
        });
    }


    /**
     * @NoAdminRequired
     *
     * @param string $content
     */
    public function create($content="") {
        $note = $this->notesService->create($this->userId);
        $note = $this->notesService->update(
            $note->getId(), $content, $this->userId
        );
        return new DataResponse($note);
    }


    /**
     * @NoAdminRequired
     *
     * @param int $id
     * @param string $content
     * @param string $category
     * @return DataResponse
     */
    public function update($id, $content, $category) {
        return $this->respond(function () use ($id, $content, $category) {
            return $this->notesService->update($id, $content, $this->userId, $category);
        });
    }


    /**
     * @NoAdminRequired
     *
     * @param int $id
     * @param boolean $favorite
     * @return DataResponse
     */
    public function favorite($id, $favorite) {
        return $this->respond(function () use ($id, $favorite) {
            return $this->notesService->favorite($id, $favorite, $this->userId);
        });
    }


    /**
     * @NoAdminRequired
     *
     * @param int $id
     * @return DataResponse
     */
    public function destroy($id) {
        return $this->respond(function () use ($id) {
            $this->notesService->delete($id, $this->userId);
            return [];
        });
    }

}
