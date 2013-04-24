(function(angular, $, undefined){

'use strict';

/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com> 
 * This file is licensed under the Affero General Public License version 3 or later. 
 * See the COPYING file.
 */

// Create the main module and add OC (appframework js) to the container
// and register routes so the url is cool :)
var app = angular.module('Notes', ['OC']);

// This will be executed directly after angular has finished to initialize
app.run(['Storage', '$rootScope', function(Storage, $rootScope){

	// loads the notes from the server
	Storage.getAll(function() {
		$rootScope.$broadcast('notesLoaded');
	});

}]);
// This is available by using ng-controller="NotesController" in your HTML
app.controller('NotesController',

	['$scope', '$location', 'NotesModel', 'Storage', 'Loading',
	function($scope, $location, NotesModel, Storage, Loading) {

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
	};


	// save a note to the server
	Storage.prototype.save = function(note){

		this._request.post('notes_save', {
			data: {
				note: note
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
			data: {
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


	return new Storage(Loading, $rootScope, Request);
}]);
})(window.angular, jQuery);