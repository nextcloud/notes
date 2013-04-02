(function($, angular, undefined){

	'use strict';


	// instantiate app
	var app = angular.module('Notes', ['OC']);

	app.run(['Storage', function(Storage){
		Storage.getAll();
	}]);


	// controllers
	app.controller('NotesController', 
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


	// take care of fileconflicts by appending a number
	app.factory('conflictHandler', ['NotesModel', function(NotesModel){
		var handler = function(title){
			if(angular.isDefined(NotesModel.getByTitle(title))){

				var previousTitle = title;

				// count up number
				title = title.replace(/\((\d+)\)$/ig, function(match, number){
					return parseInt(number, 10) + 1;
				});

				// if title does not contain (NUMBER) add it
				if(title === previousTitle){
					title += ' (1)';
				}

				return handler(title);
			} else {
				return title;	
			}
		};

		return handler;
	}]);


	// loading spinner
	app.factory('Loading', ['_Loading', function(_Loading){
		return new _Loading();
	}]);


	app.factory('Storage', ['Loading', 'Utils', '$rootScope', 'NotesModel',
		function(Loading, Utils, $rootScope, NotesModel){
		
		var Storage = function(){

		};

		Storage.prototype.save = function(note, oldTitle){
			var url = Utils.filePath('notes', 'ajax', 'save.php');
			var data = {
				oldname: oldTitle,
				content: note.content,
				category: ''
			};

			$.post(url, data);
		};

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


	// used to store the data
	app.factory('NotesModel', ['_Model', '_EqualQuery', '_MaximumQuery',
		function(_Model, _EqualQuery, _MaximumQuery){

		var NotesModel = function(){

		};

		NotesModel.prototype = new _Model();


		NotesModel.prototype.add = function(data) {

			// in case there is no id, get the highest id
			var query = new _MaximumQuery('id');
			var result = this.get(query);

			var id = 1;
			// if there is no id (no notes), start with 1
			if(angular.isDefined(result)){
				id = result.id + 1;				
			}

			data.id = id;

			_Model.prototype.add.call(this, data);
		};

		NotesModel.prototype.getNewest = function() {
			var query = new _MaximumQuery('modified');
			return this.get(query);
		};

		NotesModel.prototype.getByTitle = function(title){
			var query = new _EqualQuery('title', title);
			return this.get(query);
		};

		return new NotesModel();
	}]);


})(jQuery, angular);


