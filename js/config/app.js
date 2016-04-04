/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

/* jshint unused: false */
var app = angular.module('Notes', ['restangular', 'ngRoute']).
config(function($provide, $routeProvider, RestangularProvider, $httpProvider,
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
            note: function ($route, $q, is, Restangular) {

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
            }
        }
    }).otherwise({
        redirectTo: '/'
    });

    var baseUrl = OC.generateUrl('/apps/notes');
    RestangularProvider.setBaseUrl(baseUrl);



}).run(function ($rootScope, $location, NotesModel) {
    'use strict';

    $('link[rel="shortcut icon"]').attr(
		    'href',
		    OC.filePath('notes', 'img', 'favicon.png')
    );

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
});
