/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

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