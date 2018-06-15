<?php
namespace OCA\Notes\Controller;
use OCP\AppFramework\Controller;

use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Files\IRootFolder;

class SettingsController extends Controller
{
	private $config;
	private $userSession;
	private $root;
	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		IUserManager $userManager,
		IUserSession $userSession,
		IRootFolder $rootFolder
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->userSession = $userSession;
		$this->root = $rootFolder;
	}

    /**
     * @NoAdminRequired
     */
    public function setNotesPath($notesPath) {
	    $uid = $this->userSession->getUser()->getUID();

	    $path = '/' . $uid . '/files' . $notesPath;
	    if($this->root->isCreatable($path)) {
		    $this->config->setUserValue($uid, 'notes', 'notesPath', $notesPath);
		    return ['status' => 'success', 'notesPath' => $notesPath];
	    }

        return [
            'status' => 'error',
	        'reason' => 'invalidPath',
	        'notesPath' => '',
	    ];
    }
}
