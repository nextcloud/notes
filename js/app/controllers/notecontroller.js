/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

app.controller('NoteController', ['$routeParams', '$scope', 'NotesModel',
	'SaveQueue', 'note',
	function($routeParams, $scope, NotesModel, SaveQueue, note) {

	NotesModel.updateIfExists(note);

	$scope.note = NotesModel.get($routeParams.noteId);

	$scope.updateTitle = function () {
		$scope.note.title = $scope.note.content.split('\n')[0] ||
			$scope.translations['New note'];
	};

	$scope.save = function() {
		var note = $scope.note;
		SaveQueue.add(note);
	};

}]);