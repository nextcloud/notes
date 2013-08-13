/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

app.controller('NoteController', ['$routeParams', '$scope', 'NotesModel', 'note',
	function($routeParams, $scope, NotesModel, note) {

	NotesModel.update(note);

	$scope.note = NotesModel.get($routeParams.noteId);

	$scope.save = function() {
		var note = $scope.note;

		// create note title by using the first line
		note.title = note.content.split('\n')[0] || 'Empty note';
		note.put();
	};

}]);