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
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OC\Encryption\Exceptions\DecryptionFailedException;
use OCP\IRequest;
use OCP\IConfig;

use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\NoteDoesNotExistException;

/**
 * Class PageController
 *
 * @package OCA\Notes\Controller
 */
class PageController extends Controller {

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
    public function __construct($AppName, IRequest $request, $UserId,
                                NotesService $notesService, IConfig $settings){
        parent::__construct($AppName, $request);
        $this->notesService = $notesService;
        $this->userId = $UserId;
        $this->settings = $settings;
    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return TemplateResponse
     */
    public function index() {
        $lastViewedNote = (int) $this->settings->getUserValue($this->userId,
            $this->appName, 'notesLastViewedNote');
        // check if note exists
        try {
            $this->notesService->get($lastViewedNote, $this->userId);
            $errorMessage=null;
        } catch(NoteDoesNotExistException $ex) {
            $lastViewedNote = 0;
            $errorMessage="The last viewed note cannot be accessed. ".$ex->getMessage();
        } catch(DecryptionFailedException $ex){
            $lastViewedNote = 0;
            $errorMessage=$ex->getMessage();
        }

        $response = new TemplateResponse(
            $this->appName,
            'main',
            [
                 'lastViewedNote'=>$lastViewedNote,
                 'errorMessage'=>$errorMessage
            ]
        );

        $csp = new ContentSecurityPolicy();
        $csp->addAllowedImageDomain('*');
        $response->setContentSecurityPolicy($csp);

        return $response;
    }


}