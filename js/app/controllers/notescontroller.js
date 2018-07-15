/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

// This is available by using ng-controller="NotesController" in your HTML
app.controller('NotesController', function($routeParams, $scope, $location,
                                           Restangular, NotesModel, $window) {
    'use strict';

    $scope.route = $routeParams;
    $scope.notesLoaded = false;
    $scope.notes = NotesModel.getAll();

    var notesResource = Restangular.all('notes');

    // initial request for getting all notes
    notesResource.getList().then(function (notes) {
        NotesModel.addAll(notes);
        $scope.notesLoaded = true;
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

    $scope.toggleFavorite = function (noteId, event) {
        var note = NotesModel.get(noteId);
        note.customPUT({favorite: !note.favorite},
            'favorite', {}, {}).then(function (favorite) {
            note.favorite = favorite ? true : false;
        });
        event.target.blur();
    };


    $window.onbeforeunload = function() {
        var notes = NotesModel.getAll();
        for(var i=0; i<notes.length; i+=1) {
            if(notes[i].unsaved) {
                return t('notes', 'There are unsaved notes. Leaving ' +
                                  'the page will discard all changes!');
            }
        }
        return null;
    };
});
