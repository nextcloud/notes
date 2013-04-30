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
			// note for saving the next entry
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