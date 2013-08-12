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
		controller: 'NoteController',
		resolve: {
			// $routeParams does not work inside resolve so use $route
			// note is the name of the argument that will be injected into the
			// controller
			note: ['$route', '$q', 'is', 'Restangular', 
			function ($route, $q, is, Restangular) {
				var deferred = $q.defer();
				is.loading = true;

				Restangular.one('notes', $route.current.params.noteId).get().
				then(function (note) {
					is.loading = false;
					deferred.resolve(note);
				}, function () {
					is.loading = false;
					deferred.reject();
				});

				return deferred.promise;
			}]
		}
	});

	// dynamically set base URL for HTTP requests, assume that there is no other
	// index.php in the routes
	var $window = $windowProvider.$get();
	var url = $window.location.href;
	var baseUrl = url.split('index.php')[0] + 'index.php/apps/notes';
	RestangularProvider.setBaseUrl(baseUrl);

	// Always send the CSRF token by default
	$httpProvider.defaults.headers.common.requesttoken = oc_requesttoken;

// bind global configuration to rootscope
}]);
