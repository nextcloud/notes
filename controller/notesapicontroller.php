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

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Db\Note;

/**
 * Class NotesApiController
 *
 * @package OCA\Notes\Controller
 */
class NotesApiController extends ApiController {

    use Errors;

    /** @var NotesService */
    private $service;
    /** @var string */
    private $userId;

    /**
     * @param string $AppName
     * @param IRequest $request
     * @param NotesService $service
     * @param string $UserId
     */
    public function __construct($AppName, IRequest $request,
                                NotesService $service, $UserId){
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->userId = $UserId;
    }


    /**
     * @param Note $note
     * @param string[] $exclude the fields that should be removed from the
     * notes
     * @return Note
     */
    private function excludeFields(Note $note, array $exclude) {
        if(count($exclude) > 0) {
            foreach ($exclude as $field) {
                if(property_exists($note, $field)) {
                    unset($note->$field);
                }
            }
        }
        return $note;
    }


    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     *
     * @param string $exclude
     * @return DataResponse
     */
    public function index($exclude='') {
        $exclude = explode(',', $exclude);
        $notes = $this->service->getAll($this->userId);
        foreach ($notes as $note) {
            $note = $this->excludeFields($note, $exclude);
        }
        return new DataResponse($notes);
    }


    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     *
     * @param int $id
     * @param string $exclude
     * @return DataResponse
     */
    public function get($id, $exclude='') {
        $exclude = explode(',', $exclude);

        return $this->respond(function () use ($id, $exclude) {
            $note = $this->service->get($id, $this->userId);
            $note = $this->excludeFields($note, $exclude);
            return $note;
        });
    }


    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     *
     * @param string $content
     * @return DataResponse
     */
    public function create($content) {
        return $this->respond(function () use ($content) {
            $note = $this->service->create($this->userId);
            return $this->service->update($note->getId(), $content, $this->userId);
        });
    }


    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     *
     * @param int $id
     * @param string $content
     * @param boolean $favorite
     * @return DataResponse
     */
    public function update($id, $content=null, $favorite=null) {
        if($favorite!==null) {
            $this->service->favorite($id, $favorite, $this->userId);
        }
        return $this->respond(function () use ($id, $content) {
            if($content===null) {
                return $this->service->get($id, $this->userId);
            } else {
                return $this->service->update($id, $content, $this->userId);
            }
        });
    }


    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     *
     * @param int $id
     * @return DataResponse
     */
    public function destroy($id) {
        return $this->respond(function () use ($id) {
            $this->service->delete($id, $this->userId);
            return [];
        });
    }


}
