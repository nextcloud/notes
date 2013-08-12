
var app = angular.module('Notes', ['restangular']).
config(['$provide', '$routeProvider', 'RestangularProvider', '$httpProvider',
		'$windowProvider',
	function($provide, $routeProvider, RestangularProvider, $httpProvider,
			$windowProvider) {
	
	// you have to use $provide inside the config method to provide a globally
	// shared and injectable object
	$provide.value('Config', {
		saveInterval: 5*1000  // miliseconds
	});

	// define your routes that that load templates into the ng-view
	$routeProvider.when('/notes/:noteId', {
		templateUrl: 'note.html',
		controller: 'NoteController',
		resolve: {
			// $routeParams does not work inside resolve so use $route
			// note is the name of the argument that will be injected into the
			// controller
			note: ['$route', '$q', 'is', 'Restangular', 
			function ($route, $q, is, Restangular) {
				var deferred = $q.defer();
				is.loading = true;

				Restangular.one('notes', $route.current.params.noteId).get().
				then(function (note) {
					is.loading = false;
					deferred.resolve(note);
				}, function () {
					is.loading = false;
					deferred.reject();
				});

				return deferred.promise;
			}]
		}
	});

	// dynamically set base URL for HTTP requests, assume that there is no other
	// index.php in the routes
	var $window = $windowProvider.$get();
	var url = $window.location.href;
	var baseUrl = url.split('index.php')[0] + 'index.php/apps/notes';
	RestangularProvider.setBaseUrl(baseUrl);

	// Always send the CSRF token by default
	$httpProvider.defaults.headers.common.requesttoken = oc_requesttoken;

// bind global configuration to rootscope
}]);

app.controller('AppController', ['$scope', 'is', function ($scope, is) {
	$scope.is = is;
}]);
app.controller('NoteController', ['$routeParams', '$scope', 'NotesModel', 'note',
	function($routeParams, $scope, NotesModel, note) {

	$scope.note = NotesModel.get($routeParams.noteId);

	$scope.save = function() {
		var note = $scope.note;

		// create note title by using the first line
		note.title = note.content.split('\n')[0] || 'Empty note';
		note.put().then(function (updated) {
			NotesModel.update(updated);
		});
	};


}]);
// This is available by using ng-controller="NotesController" in your HTML
app.controller('NotesController', ['$routeParams', '$scope', '$location', 
	'Restangular', 'NotesModel', 'Config',
	function($routeParams, $scope, $location, Restangular, NotesModel, Config) {
	
	$scope.route = $routeParams;

	var notesResource = Restangular.all('notes');

	// initial request for getting all notes
	notesResource.getList().then(function (notes) {
		NotesModel.addAll(notes);
		$scope.notes = NotesModel.getAll();
	});

	$scope.create = function () {
		notesResource.post().then(function (note) {
			NotesModel.add(note);
			$location.path('/notes/' + note.id);
		});

	};

}]);

/**
 * Like ng-change only that it does not fire when you type faster than
 * 300 ms
 */
app.directive('notesTimeoutChange', ['$timeout', function ($timeout) {
	return {
		restrict: 'A',
		link: function (scope, element, attributes) {
			var interval = 300;  // 300 miliseconds timeout after typing
			var lastChange = new Date().getTime();
			var timeout;

			$(element).keyup(function () {
				var now = new Date().getTime();
				
				if(now - lastChange < interval) {
					$timeout.cancel(timeout);
				}

				timeout = $timeout(function () {
					scope.$apply(attributes.notesTimeoutChange);
				}, interval);

				lastChange = now;
			});
		}
	};
}]);

app.factory('is', function () {
	return {
		loading: false
	};
});
// take care of fileconflicts by appending a number
app.factory('NotesModel', function () {
	var NotesModel = function () {
		this.notes = [];
		this.notesIds = {};
	};

	NotesModel.prototype = {
		addAll: function (notes) {
			for(var i=0; i<notes.length; i++) {
				this.add(notes[i]);
			}
		},
		add: function(note) {
			this.notes.push(note);
			this.notesIds[note.id] = note;
		},
		getAll: function () {
			return this.notes;
		},
		get: function (id) {
			return this.notesIds[id];
		},
		update: function(updated) {
			var keys = Object.keys(updated);
			var note = this.notesIds[updated.id];

			for(var i=0; i<keys.length; i++) {
				var key = keys[i];
				
				if(key !== 'id') {
					note[key] = updated[key];
				}
			}
		}
	};

	return new NotesModel();
});
