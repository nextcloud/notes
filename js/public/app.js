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

	['$q', 'Request', 'Loading',
	function($q, Request, Loading){


	var Storage = function($q, request, loading){
		this._$q = $q;
		this._loading = loading;
		this._request = request;
		this._updating = {};
	};


	// create and save a new note to the server
	Storage.prototype.create = function() {
		var self = this;

		// this lets us bind success and failure methods to an object
		// the object will be returned and gives us the possibility
		// to chain success and failure callbacks like
		// Storage.create().then(
		//	function(){ alert('success'); }, 
		//  function(){ alert('failure'); }
		// );
		var deferred = this._$q.defer();

		this._loading.increase();

		this._request.post('notes_create', {
			onSuccess: function(data) {
				// because the $http object will call $scope.$apply its not
				// needed in here
				deferred.resolve(data);
				self._loading.decrease();
			},
			onFailure: function() {
				deferred.reject();
				self._loading.decrease();
			}
		});

		return deferred.promise;
	};


	// update a note
	Storage.prototype.update = function(note){
		var self = this;
		var deferred = this._$q.defer();

		this._updating[note.id] = this._updating[note.id] || {};
		var updating = this._updating[note.id];

		// if theres already an update request, save this one and exit
		if(angular.isDefined(updating.current)) {

			updating.next = note;
			updating.promise = deferred.promise;

		// if there is no current note execute the request and also
		// execute saved ones
		} else {

			updating.current = note;

			// execute the newest request if there is any
			var nextUpdateCallback = function() {
				delete updating.current;

				if(angular.isDefined(updating.next)) {
					self.update(updating.next);
					delete updating.next;
				}
			};


			this._request.put('notes_update', {
				routeParams: {
					id: note.id
				},
				onSuccess: function(data) {
					deferred.resolve(data);
					nextUpdateCallback();
				},
				onFailure: function() {
					deferred.reject();
					nextUpdateCallback();
				}
			});
		}

		return deferred.promise;
	};


	// get all from the server and populate the notes model
	Storage.prototype.getAll = function() {
		var self = this;
		var deferred = this._$q.defer();

		this._loading.increase();

		this._request.get('notes_get_all', {
			onSuccess: function(data) {
				deferred.resolve(data);
				self._loading.decrease();
			},
			onFailure: function() {
				deferred.reject();
				self._loading.decrease();
			}
		});

		return deferred.promise;
	};


	// update the note by id
	Storage.prototype.getById = function(id) {
		var self = this;
		var deferred = this._$q.defer();

		this._loading.increase();

		this._request.get('notes_get', {
			routeParams: {
				id: id
			},
			onSuccess: function(data) {
				self._loading.decrease();
				deferred.resolve(data);
			},
			onFailure: function() {
				self._loading.decrease();
				deferred.reject();
			}
		});

		return deferred.promise;
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


	return new Storage($q, Request, Loading);
}]);
})(window.angular, jQuery);