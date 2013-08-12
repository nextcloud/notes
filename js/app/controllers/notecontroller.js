/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

app.controller('NoteController', ['$routeParams', '$scope', 'NotesModel', 'note',
	function($routeParams, $scope, NotesModel, note) {

	// update currently available note
	var oldNote = NotesModel.update(note);
	if(oldNote) {
		oldNote.content = note.content;
		oldNote.title = note.title;
		oldNote.modified = note.modified;
		oldNote.id = note.id;
	}
	$scope.note = oldNote;

	$scope.save = function() {
		var note = $scope.note;

		// create note title by using the first line
		note.title = note.content.split('\n')[0] || 'Empty note';
		console.log(note);
		//note.put();
	};


}]);