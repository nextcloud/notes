/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

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