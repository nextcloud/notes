/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file. 
 */


// This is available by using ng-controller="NotesController" in your HTML
app.controller('NotesController', 

	// inject the dependencies
	['$scope', 'Storage', 'NotesModel', 'conflictHandler', 'Loading',
	function($scope, Storage, NotesModel, conflictHandler, Loading) {


	$scope.notes = NotesModel.getAll();

	// load a new note into the right field
	$scope.load = function(note){
		$scope.activeNote = note;
	};

	// loading spinner
	$scope.loading = Loading;

	// if all notes are loaded, load the newest one
	$scope.$on('loaded', function(){
		if(NotesModel.size() > 0){
			$scope.load(NotesModel.getNewest());
		}
	});

	// every time you type, the note is being updated
	$scope.update = function(note){
		if(note.content === ''){

			// TODO: delete via ajax
			console.log('deleted');
			NotesModel.removeById(note.id);

		} else {
			// in case
			if(angular.isUndefined(NotesModel.getById(note.id))){
				$scope.createNew();
				$scope.activeNote.content = note.content;
			}
			var oldTitle = $scope.activeNote.title;
			// TODO: conflict resolution
			// $scope.activeNote.title = 
			//	conflictHandler(note.content.substr(0, 50));
			$scope.activeNote.title = note.content.substr(0, 50);
			$scope.activeNote.modified = new Date().getTime();

			console.log('saving');
			Storage.save(note, oldTitle);
		}
		
	};

	// create a new note when you click on the + button
	$scope.createNew = function() {
		var newNote = {
			title: '',
			content: '',
			modified: new Date().getTime()
		};
		NotesModel.add(newNote);
		$scope.activeNote = newNote;
	};


}]);
