(function(angular, $, undefined){

'use strict';

/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com> 
 * This file is licensed under the Affero General Public License version 3 or later. 
 * See the COPYING file.
 */

// Create the main module and add OC (appframework js) to the container
// and register routes so the url is cool :)
var app = angular.module('Notes', ['OC']).
config(['$provide', function($provide) {
	$provide.value('Config', {
		saveInterval: 15*1000  // miliseconds
	});
}]);

// This will be executed directly after angular has finished to initialize
app.run(['Storage', function(Storage){
	Storage.getAll(); // loads the notes from the server
}]);
// This is available by using ng-controller="NotesController" in your HTML
app.controller('NotesController',

	['$scope', '$location', '$timeout', 'NotesModel', 'Storage', 'Loading',
	'Config',
	function($scope, $location, $timeout, NotesModel, Storage, Loading, Config) {


	// extracts the id from the url
	var getNoteId = function() {
		return parseInt( $location.path().substring(1), 10 );
	};

	// load a note from the server if it does not yet exist
	var updateNote = function(id) {
		var note = NotesModel.getById(id);

		// in case the note does not exist yet locally
		if(angular.isDefined(note) && note.content === '') {
			Storage.getById(id);
		}

		$scope.activeNote = note;
	};

	// we want to check if the # changes in the url and conditionally load the
	// note if it does not have content. Consider using Angular routes if your
	// system is more complex
	$scope.$watch(getNoteId, updateNote);
	$scope.$on('notesLoaded', function() {
		updateNote(getNoteId());
	});


	// bind all the notes to the scope
	$scope.notes = NotesModel.getAll();

	// loading spinner
	$scope.loading = Loading;

	// notes should be saved if they are dirty only, see ng-change in the
	// template
	setInterval(function() {

		angular.forEach($scope.notes, function(note) {
			if(note.dirty) {
				Storage.save(note);
				note.dirty = false;
			}
		});

	}, Config.saveInterval);
}]);

// take care of fileconflicts by appending a number
app.factory('conflictHandler', ['NotesModel', function(NotesModel){

	var handler = function(title){
		if(NotesModel.getByTitle(title).length > 0){
			var previousTitle = title;

			// count up number
			title = title.replace(/\((\d+)\)$/ig, function(match, number){
				var nextNumber = parseInt(number, 10) + 1;
				return '(' + nextNumber + ')';
			});

			// if title does not contain (NUMBER) add it
			if(title === previousTitle){
				title += ' (2)';
			}

			return handler(title);
		} else {
			return title;
		}
	};

	return handler;
}]);

// used to store the notes data and create hashes and caches for quick access
app.factory('NotesModel',

	['_Model', '_EqualQuery', '_MaximumQuery',
	function(_Model, _EqualQuery, _MaximumQuery){

	var NotesModel = function(){};
	NotesModel.prototype = new _Model();

	// overwrite to set an id
	NotesModel.prototype.add = function(data) {
		_Model.prototype.add.call(this, data);
	};


	NotesModel.prototype.getNewest = function() {
		var query = new _MaximumQuery('modified');
		return this.get(query);
	};


	NotesModel.prototype.getByTitle = function(title){
		var query = new _EqualQuery('title', title);
		return this.get(query);
	};


	return new NotesModel();

}]);

// Use this to instantiate and build the objects from the appframework


// dependency of the request object
app.factory('Publisher',

	['_Publisher', 'NotesModel',
	function(_Publisher, NotesModel) {


	var publisher = new _Publisher();

	// distribute all content that is being returned in the data.notes json array
	// to the model. This adds new notes and updates existing ones
	publisher.subscribeObjectTo(NotesModel, 'notes');

	return publisher;

}]);


// this allows you to make ajax requests 
app.factory('Request',

	['_Request', '$http', 'Publisher', 'Router',
	function(_Request, $http, Publisher, Router) {

	return new _Request($http, Publisher, Router);

}]);


// loading spinner
app.factory('Loading', ['_Loading', function(_Loading){
	return new _Loading();
}]);

// class for loading and saving
app.factory('Storage',

	['Loading', '$rootScope', 'Request',
	function(Loading, $rootScope, Request){


	var Storage = function(loading, $rootScope, request){
		this._loading = loading;
		this._$rootScope = $rootScope;
		this._request = request;
		this._saving = {};
		this._saveQueue = [];
	};


	// save a note to the server
	Storage.prototype.save = function(note){
		var self = this;

		// first make sure that only one note can be saved at a time
		// to do that check if the note is currently being saved in the 
		// saving hashmap and queue it if needed
		var saveQueue = this._saving[note.id] || [];

		saveQueue.push(note);

		if(saveQueue.length > 1) {
			return;
		}

		// on successful save we can unluck the note and execute queued up
		// requests
		var onSuccess = function() {

			// remove the just saved item
			var saveQueue = self._saving[note.id];
			saveQueue.shift();

			// if there are any left, take the last one because its the
			// newest
			if(saveQueue.length > 0) {
				var nextNote = saveQueue[saveQueue.length-1];

				self._saving[note.id].length = 0;

				self.save(nextNote);
			}
		};


		this._request.post('notes_save', {
			data: {
				id: note.id,
				content: note.content
			},
			onSuccess: onSuccess,
			// if saving failed, bad luck ;D, let the user know and unlock the
			// note for saving
			onFailure: function () {
				$rootScope.$broadcast('noteSaveFailed');

				onSuccess();
			}
		});

	};


	// get all from the server and populate the notes model
	Storage.prototype.getAll = function() {
		var self = this;

		this._loading.increase();

		this._request.get('notes_get_all', {
			onSuccess: function() {
				$rootScope.$broadcast('notesLoaded');
				self._loading.decrease();
			},
			onFailure: function() {
				self._loading.decrease();
			}
		});
	};


	// update the note by id
	Storage.prototype.getById = function(id) {
		var self = this;

		this._loading.increase();

		this._request.get('notes_get', {
			routeParams: {
				id: id
			},
			onSuccess: function() {
				self._loading.decrease();
			},
			onFailure: function() {
				self._loading.decrease();
			}
		});
	};


	// delete the note
	Storage.prototype.deleteById = function(id) {
		/*jslint es5: true */
		this._request.delete('notes_delete', {
			routeParams: {
				id: id
			}
		});
	};


	return new Storage(Loading, $rootScope, Request);
}]);
})(window.angular, jQuery);