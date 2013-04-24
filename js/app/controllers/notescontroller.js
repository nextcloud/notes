/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */


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
