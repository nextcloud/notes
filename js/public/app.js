
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
				var noteId = $route.current.params.noteId;
				is.loading = true;

				Restangular.one('notes', noteId).get().then(function (note) {
					is.loading = false;
					deferred.resolve(note);
				}, function () {
					is.loading = false;
					deferred.reject();
				});

				return deferred.promise;
			}]
		}
	}).otherwise({
		redirectTo: '/'
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
}]).run(['$rootScope', '$location', 'NotesModel',
	function ($rootScope, $location, NotesModel) {
	$rootScope.$on('$routeChangeError', function () {
		var notes = NotesModel.getAll();

		if (notes.length > 0) {
			var sorted = notes.sort(function (a, b) {
				if(a.modified > b.modified) return 1;
				if(a.modified < b.modified) return -1;
				return 0;
			});

			var note = notes[notes.length-1];
			$location.path('/notes/' + note.id);
		} else {
			$location.path('/');
		}
	});
}]);

app.controller('AppController', ['$scope', '$location', 'is',
	function ($scope, $location, is) {
	$scope.is = is;

	$scope.init = function (lastViewedNote) {
		if(lastViewedNote !== 0) {
			$location.path('/notes/' + lastViewedNote);
		}
	};
}]);
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
			var timeout;

			$(element).change(function () {
				var now = new Date().getTime();
				$timeout.cancel(timeout);

				timeout = $timeout(function () {
					scope.$apply(attributes.notesTimeoutChange);
				}, interval);

				lastChange = now;
			});
		}
	};
}]);

/**
 * Binds translated values to scope and hides the element
 */
app.directive('notesTranslate', function () {
	return {
		restrict: 'E',
		link: function (scope, element, attributes) {
			var $element = $(element);
			$element.hide();
			scope.translations = scope.translations || {};
			scope.translations[attributes.key] = $element.text();
		}
	};
});

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
		updateIfExists: function(updated) {
			var note = this.notesIds[updated.id];
			if(angular.isDefined(note)) {
				note.title = updated.title;
				note.modified = updated.modified;
				note.content = updated.content;
			} else {
				this.add(updated);
			}
		}
	};

	return new NotesModel();
});
app.factory('SaveQueue', ['$q', function($q) {
	var SaveQueue = function () {
		this.queue = {};
		this.flushLock = false;
	};

	SaveQueue.prototype = {
		add: function (note) {
			this.queue[note.id] = note;
			this.flush();
		},
		flush: function () {
			// if there are no changes dont execute the requests
			var keys = Object.keys(this.queue);
			if(keys.length === 0 || this.flushLock) {
				return;
			} else {
				this.flushLock = true;
			}

			var self = this;
			var requests = [];

			for(var i=0; i<keys.length; i++) {
				var note = this.queue[keys[i]];
				requests.push(note.put().then(this._noteUpdateRequest.bind(null, note)));
			}
			this.queue = {};

			$q.all(requests).then(function () {
				self.flushLock = false;
				self.flush();
			});
		},
		_noteUpdateRequest: function (note, response) {
			note.title = response.title;
			note.modified = response.modified;
		}
	};

	return new SaveQueue();
}]);
