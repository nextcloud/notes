/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

// class for loading and saving
app.factory('Storage',

	['Loading', 'Utils', '$rootScope', 'NotesModel',
	function(Loading, Utils, $rootScope, NotesModel){

	var Storage = function(){};

	// save a note to the server
	Storage.prototype.save = function(note, oldTitle){
		var url = Utils.filePath('notes', 'ajax', 'save.php');
		var data = {
			oldname: oldTitle,
			content: note.content,
			category: ''
		};

		$.post(url, data);
	};


	// get all from the server and populate the notes model
	Storage.prototype.getAll = function() {
		Loading.increase();

		var url = Utils.filePath('notes', 'ajax', 'get.php');
		var data = {};

		$.post(url, data, function(json){

			angular.forEach(json.data, function(data){
				var note = {
					title: data.content.substr(0, 50),
					content: data.content,
					modified: data.modified
				};

				NotesModel.add(note);
			});

			$rootScope.$broadcast('loaded');

			Loading.decrease();

			// because we make requests with jquery instead of $http
			// we have to tell angular manually that something changed
			$rootScope.$apply();
		});
	};


	return new Storage();
}]);