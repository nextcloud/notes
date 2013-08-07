/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

// This is available by using ng-controller="NotesController" in your HTML
app.controller('NotesController', ['$routeParams', '$scope', 'Restangular',
	'NotesModel', 'Config',
	function($routeParams, $scope, Restangular, NotesModel, Config) {
	
	$scope.route = $routeParams;

	// initial request for getting all notes
	Restangular.all('notes').getList().then(function (notes) {

		NotesModel.addAll(notes);
		$scope.notes = NotesModel.getAll();

	});

	$scope.create = function () {
		console.log('tbd');
	};

}]);
