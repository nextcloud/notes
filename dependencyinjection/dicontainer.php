<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\DependencyInjection;

use \OC\Files\View;

use \OCA\AppFramework\DependencyInjection\DIContainer as BaseContainer;

use \OCA\Notes\Controller\PageController;
use \OCA\Notes\Controller\NotesController;

use \OCA\Notes\Service\NotesService;

use \OCA\Notes\Utility\FileSystemUtility;


class DIContainer extends BaseContainer {


	/**
	 * Define your dependencies in here
	 */
	public function __construct(){
		// tell parent container about the app name
		parent::__construct('notes');


		/** 
		 * Controllers
		 */
		$this['PageController'] = $this->share(function($c){
			return new PageController($c['API'], $c['Request']);
		});


		$this['NotesController'] = $this->share(function($c){
			return new NotesController($c['API'], $c['Request'],
				$c['NotesService']);
		});

		/**
		 * Services
		 */
		$this['NotesService'] = $this->share(function($c){
			return new NotesService($c['FileSystem'], $c['FileSystemUtility']);
		});


		/**
		 * Utilities
		 */
		$this['FileSystem'] = $this->share(function($c){
			$userName = $c['API']->getUserId();

			$view = new View('/' . $userName . '/files/Notes'); 
			if (!$view->file_exists('/')) {
				$view->mkdir('/');
			}

			return $view;
		});

		$this['FileSystemUtility'] = $this->share(function($c){
			return new FileSystemUtility($c['FileSystem']);
		});

		

	}
}

