(function(angular, $, requestToken, marked, hljs, undefined){

'use strict';


/* jshint unused: false */
var app = angular.module('Notes', ['restangular', 'ngRoute']).
config(["$provide", "$routeProvider", "RestangularProvider", "$httpProvider", "$windowProvider", function($provide, $routeProvider, RestangularProvider, $httpProvider,
                $windowProvider) {
    'use strict';

    // Always send the CSRF token by default
    $httpProvider.defaults.headers.common.requesttoken = requestToken;

    // you have to use $provide inside the config method to provide a globally
    // shared and injectable object
    $provide.value('Constants', {
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
            /* @ngInject */
            note: ["$route", "$q", "is", "Restangular", function ($route, $q, is, Restangular) {

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



}]).run(["$rootScope", "$location", "NotesModel", "Config", function ($rootScope, $location, NotesModel, Config) {
    'use strict';

    // get config
    Config.load();

    // handle route errors
    $rootScope.$on('$routeChangeError', function () {
        var notes = NotesModel.getAll();

        // route change error should redirect to the latest note if possible
        if (notes.length > 0) {
            var sorted = notes.sort(function (a, b) {
                if(a.modified > b.modified) {
                    return 1;
                } else if(a.modified < b.modified) {
                    return -1;
                } else {
                    return 0;
                }
            });

            var note = notes[sorted.length-1];
            $location.path('/notes/' + note.id);
        } else {
            $location.path('/');
        }
    });
}]);

app.controller('AppController', ["$scope", "$location", "is", function ($scope, $location, is) {
    'use strict';

    $scope.is = is;

    $scope.init = function (lastViewedNote) {
        if(lastViewedNote !== 0) {
            $location.path('/notes/' + lastViewedNote);
        }
    };
}]);
app.controller('NoteController', ["$routeParams", "$scope", "NotesModel", "SaveQueue", "note", "Config", function($routeParams, $scope, NotesModel,
                                          SaveQueue, note, Config) {
    'use strict';

    NotesModel.updateIfExists(note);

    $scope.note = NotesModel.get($routeParams.noteId);
    $scope.config = Config;
    $scope.markdown = Config.isMarkdown();

    $scope.isSaving = function () {
        return SaveQueue.isSaving();
    };

    $scope.updateTitle = function () {
        $scope.note.title = $scope.note.content.split('\n')[0] ||
            t('notes', 'New note');
    };

    $scope.save = function() {
        var note = $scope.note;
        SaveQueue.add(note);
    };

    $scope.sync = function (markdown) {
        Config.setIsMarkdown(markdown);
        Config.sync();
    };

}]);
// This is available by using ng-controller="NotesController" in your HTML
app.controller('NotesController', ["$routeParams", "$scope", "$location", "Restangular", "NotesModel", function($routeParams, $scope, $location,
                                           Restangular, NotesModel) {
    'use strict';

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

app.directive('notesAutofocus', function () {
    'use strict';
    return {
        restrict: 'A',
        link: function (scope, element) {
            element.focus();
        }
    };
});

app.directive('notesIsSaving', ["$window", function ($window) {
    'use strict';
    return {
        restrict: 'A',
        scope: {
            'notesIsSaving': '='
        },
        link: function (scope) {
            $window.onbeforeunload = function () {
                if (scope.notesIsSaving) {
                    return t('notes', 'Note is currently saving. Leaving ' +
                                      'the page will delete all changes!');
                } else {
                    return null;
                }
            };
        }
    };
}]);

app.directive('markdown', function () {
    'use strict';

    marked.setOptions({
        sanitize: true,
        gfm: true,
        tables: true,
        breaks: true,
        smartLists: true,
        highlight: function (code) {
            return hljs.highlightAuto(code).value;
        }
    });
    return {
        restrict: 'AE',
        link: function (scope, element, attrs) {
            if (attrs.markdown) {
                scope.$watch(attrs.markdown, function (newVal) {
                    var html = marked(newVal);
                    element.html(html);
                });
            } else {
                var html = marked(element.text());
                element.html(html);
            }
        }
    };
});

app.directive('editor', ['$timeout', function ($timeout) {
	return {
		restrict: 'A',
		link: function(scope, element, attrs) {
			var editor = mdEdit(element[0], {change: function(value) {
				$timeout(function(){
					scope.$apply(function() {
						scope.note.content = value;
						scope.updateTitle();
					});
				});
			}});
			editor.setValue(scope.note.content);
		}
	}
}]);

/**
 * Like ng-change only that it does not fire when you type faster than
 * 300 ms
 */
app.directive('notesTimeoutChange', ["$timeout", function ($timeout) {
    'use strict';

    return {
        restrict: 'A',
        link: function (scope, element, attributes) {
            var interval = 300;  // 300 miliseconds timeout after typing
            var timeout;

            $(element).bind('input propertychange', function () {
                $timeout.cancel(timeout);

                timeout = $timeout(function () {
                    scope.$apply(attributes.notesTimeoutChange);
                }, interval);
            });
        }
    };
}]);

app.directive('notesTooltip', function () {
    'use strict';

    return {
        restrict: 'A',
        link: function (scope, element) {
            element.tooltip();
        }
    };
});

app.factory('Config', ["Restangular", function (Restangular) {
    'use strict';

    var Config = function (Restangular) {
        this._markdown = false;
        this._Restangular = Restangular;
    };

    Config.prototype.load = function () {
        var self = this;
        this._Restangular.one('config').get().then(function (config) {
            self._markdown = config.markdown;
        });
    };

    Config.prototype.isMarkdown = function () {
        return this._markdown;
    };

    Config.prototype.setIsMarkdown = function (isMarkdown) {
        this._markdown = isMarkdown;
    };

    Config.prototype.sync = function () {
        return this._Restangular.one('config').customPOST({
            markdown: this._markdown
        });
    };

    return new Config(Restangular);
}]);
app.factory('is', function () {
    'use strict';

    return {
        loading: false
    };
});
// take care of fileconflicts by appending a number
app.factory('NotesModel', function () {
    'use strict';

    var NotesModel = function () {
        this.notes = [];
        this.notesIds = {};
    };

    NotesModel.prototype = {
        addAll: function (notes) {
            for(var i=0; i<notes.length; i+=1) {
                this.add(notes[i]);
            }
        },
        add: function(note) {
            this.updateIfExists(note);
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
                this.notes.push(updated);
                this.notesIds[updated.id] = updated;
            }
        },
        remove: function (id) {
            for(var i=0; i<this.notes.length; i+=1) {
                var note = this.notes[i];
                if(note.id === id) {
                    this.notes.splice(i, 1);
                    delete this.notesIds[id];
                    break;
                }
            }
        }
    };

    return new NotesModel();
});
app.factory('SaveQueue', ["$q", function($q) {
    'use strict';

    var SaveQueue = function () {
        this._queue = {};
        this._flushLock = false;
    };

    SaveQueue.prototype = {
        add: function (note) {
            this._queue[note.id] = note;
            this._flush();
        },
        _flush: function () {
            // if there are no changes dont execute the requests
            var keys = Object.keys(this._queue);
            if(keys.length === 0 || this._flushLock) {
                return;
            } else {
                this._flushLock = true;
            }

            var self = this;
            var requests = [];

            // iterate over updated objects and run an update request for
            // each one of them
            for(var i=0; i<keys.length; i+=1) {
                var note = this._queue[keys[i]];
                // if the update finished, update the modified and title
                // attributes on the note
                requests.push(note.put().then(
                    this._noteUpdateRequest.bind(null, note))
                );
            }
            this._queue = {};

            // if all update requests are completed, run the flush
            // again to update the next batch of queued notes
            $q.all(requests).then(function () {
                self._flushLock = false;
                self._flush();
            });
        },
        _noteUpdateRequest: function (note, response) {
            note.title = response.title;
            note.modified = response.modified;
        },
        isSaving: function () {
            return this._flushLock;
        }
    };

    return new SaveQueue();
}]);

})(angular, jQuery, oc_requesttoken, marked, hljs);
