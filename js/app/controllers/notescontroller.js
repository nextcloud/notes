/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

// This is available by using ng-controller="NotesController" in your HTML
app.controller('NotesController', ['$routeParams', '$scope', '$location',
	'Restangular', 'NotesModel',
	function($routeParams, $scope, $location, Restangular, NotesModel) {

	$scope.route = $routeParams;
	$scope.notes = NotesModel.getAll();

	var notesResource = Restangular.all('notes');

	// initial request for getting all notes
	notesResource.getList().then(function (notes) {
		NotesModel.addAll(notes);
	});

	$scope.create = function () {
		notesResource.post().then(function (note) {
			NotesModel.add(note);
			$location.path('/notes/' + note.id);
		});
	};

	$scope.delete = function (noteId) {
		var note = NotesModel.get(noteId);
		note.remove().then(function () {
			NotesModel.remove(noteId);
			$scope.$emit('$routeChangeError');
		});	
	};

}]);
