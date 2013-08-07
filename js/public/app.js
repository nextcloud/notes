(function(angular, $, oc_requesttoken, undefined){

'use strict';

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

app.directive('notesTimeoutChange', ['$timeout', function ($timeout) {
	return {
		restrict: 'A',
		link: function (scope, element, attributes) {
			var interval = 300;  // 300 miliseconds timeout after typing
			var lastChange = new Date().getTime();
			var timeout;

			$(element).keyup(function () {
				var now = new Date().getTime();
				
				if(now - lastChange < interval) {
					$timeout.cancel(timeout);
				}

				timeout = $timeout(function () {
					scope.$apply(attributes.notesTimeoutChange);
				}, interval);

				lastChange = now;
			});
		}
	};
}]);

app.controller('NoteController', ['$routeParams', '$scope', 'Restangular', 
	'NotesModel',
	function($routeParams, $scope, Restangular, NotesModel) {

	// because the initial request may be very big, the content of a note is
	// not loaded. That's why it has to be loaded on demand and added here
	Restangular.one('notes', $routeParams.noteId).get().then(function (note) {

		// update currently available note
		var oldNote = NotesModel.get(note.id);

		if(oldNote) {
			oldNote.content = note.content;
			oldNote.title = note.title;
			oldNote.modified = note.modified;
			oldNote.id = note.id;
		}

		$scope.note = oldNote;
		
	});

}]);
// This is available by using ng-controller="NotesController" in your HTML
app.controller('NotesController', ['$routeParams', '$scope', 'Restangular',
	'NotesModel', 'Config',
	function($routeParams, $scope, Restangular, NotesModel, Config) {
	
	$scope.route = $routeParams;

	// initial request for getting all notes
	Restangular.all('notes').getList().then(function (notes) {

		NotesModel.addAll(notes);
		$scope.notes = NotesModel.getAll();

	});

	$scope.create = function () {
		console.log('tbd');
	};

}]);

// take care of fileconflicts by appending a number
app.factory('NotesModel', function () {
	var NotesModel = function () {
		this.notes = [];
		this.notesIds = {};
	};

	NotesModel.prototype = {
		addAll: function (notes) {
			for(var i=0; i<notes.length; i++) {
				this.add(notes[i]);
			}
		},
		add: function(note) {
			this.notes.push(note);
			this.notesIds[note.id] = note;
		},
		getAll: function () {
			return this.notes;
		},
		get: function (id) {
			return this.notesIds[id];
		}
	};

	return new NotesModel();
});
})(window.angular, jQuery, oc_requesttoken);