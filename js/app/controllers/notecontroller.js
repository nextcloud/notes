/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

app.controller('NoteController', function($routeParams, $scope, NotesModel,
                                          SaveQueue, note, debounce) {
    'use strict';

    NotesModel.updateIfExists(note);

    $scope.note = NotesModel.get($routeParams.noteId);

    $scope.isSaving = function () {
        return SaveQueue.isSaving();
    };

    $scope.updateTitle = function () {
        $scope.note.title = $scope.note.content.split('\n')[0] ||
            t('notes', 'New note');
    };

    $scope.save = debounce(function() {
        var note = $scope.note;
        SaveQueue.add(note);
    }, 300);

    $scope.toggleDistractionFree = function() {
        function launchIntoFullscreen(element) {
            if(element.requestFullscreen) {
                element.requestFullscreen();
            } else if(element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if(element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else if(element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
        }

        function exitFullscreen() {
            if(document.exitFullscreen) {
                document.exitFullscreen();
            } else if(document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if(document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
        }

        if(document.fullscreenElement ||
           document.mozFullScreenElement ||
           document.webkitFullscreenElement) {
            exitFullscreen();
        } else {
            launchIntoFullscreen(document.getElementById('app-content'));
        }
    };

});
