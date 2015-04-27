<?php
/**
 * ownCloud - Notes
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
     * @param NotesService $notesService
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
        return new DataResponse($this->notesService->getAll($this->userId));
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
     */
    public function create() {
        return new DataResponse($this->notesService->create($this->userId));
    }


    /**
     * @NoAdminRequired
     *
     * @param int $id
     * @param string $content
     * @return DataResponse
     */
    public function update($id, $content) {
        return $this->respond(function () use ($id, $content) {
            return $this->notesService->update($id, $content, $this->userId);
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


    /**
     * @NoAdminRequired
     */
    public function getConfig() {
        $markdown = $this->settings->getUserValue(
            $this->userId, $this->appName, 'notesMarkdown'
        ) === '1';

        return new DataResponse(['markdown' => $markdown]);
    }


    /**
     * @NoAdminRequired
     *
     * @param string $markdown
     * @return DataResponse
     */
    public function setConfig($markdown) {
        $this->settings->setUserValue(
            $this->userId, $this->appName, 'notesMarkdown', $markdown
        );

        return new DataResponse();
    }


}