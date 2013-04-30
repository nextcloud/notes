/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

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