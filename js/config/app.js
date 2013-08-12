/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

var app = angular.module('Notes', ['restangular']).
config(['$provide', '$routeProvider', 'RestangularProvider', '$httpProvider',
		'$windowProvider',
	function($provide, $routeProvider, RestangularProvider, $httpProvider,
			$windowProvider) {
	
	// you have to use $provide inside the config method to provide a globally
	// shared and injectable object
	$provide.value('Config', {
		saveInterval: 5*1000  // miliseconds
	});

	// define your routes that that load templates into the ng-view
	$routeProvider.when('/notes/:noteId', {
		templateUrl: 'note.html',
		controller: 'NoteController'
	});

	// dynamically set base URL for HTTP requests, assume that there is no other
	// index.php in the routes
	var $window = $windowProvider.$get();
	var url = $window.location.href;
	var baseUrl = url.split('index.php')[0] + 'index.php/apps/notes';
	RestangularProvider.setBaseUrl(baseUrl);

	// Always send the CSRF token by default
	$httpProvider.defaults.headers.common.requesttoken = oc_requesttoken;

}]);
