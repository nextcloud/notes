/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

app.controller('NoteController', function($routeParams, $scope, NotesModel,
                                          SaveQueue, note, debounce,
                                          $document, $timeout) {
    'use strict';

    NotesModel.updateIfExists(note);

    $scope.note = NotesModel.get($routeParams.noteId);

    $scope.isSaving = function () {
        return SaveQueue.isSaving();
    };
    $scope.isManualSaving = function () {
        return SaveQueue.isManualSaving();
    };

    $scope.updateTitle = function () {
        var content = $scope.note.content;

        // prepare content: remove markdown characters and empty spaces
        content = content.replace(/^\s*[*+-]\s+/mg, ''); // list item
        content = content.replace(/^#+\s+(.*?)\s*#*$/mg, '$1'); // headline
        content = content.replace(/^(=+|-+)$/mg, ''); // separate headline
        content = content.replace(/(\*+|_+)(.*?)\1/mg, '$2'); // emphasis

        // prevent directory traversal, illegal characters
        content = content.replace(/[\*\|\/\\\:\"<>\?]/g, '');
        // prevent unintended file names
        content = content.replace(/^[\. ]+/mg, '');

        // generate title from the first line of the content
        $scope.note.title = content.trim().split(/\r?\n/, 2)[0] ||
            t('notes', 'New note');
    };

    $scope.onEdit = function() {
        var note = $scope.note;
        note.unsaved = true;
        $scope.autoSave(note);
    };

    $scope.autoSave = debounce(function(note) {
        SaveQueue.add(note);
    }, 1000);

    $scope.manualSave = function() {
        var note = $scope.note;
        note.error = false;
        SaveQueue.addManual(note);
    };

    $scope.editCategory = false;
    $scope.showEditCategory = function() {
        $('#category').val($scope.note.category);
        $scope.editCategory = true;
        $('#category').autocomplete({
            source: NotesModel.getCategories(NotesModel.getAll(), 0, false),
            minLength: 0,
            position: { my: 'left bottom', at: 'left top', of: '#category' },
            open: function() {
                 $timeout(function() {
                     var width = $('form.category').innerWidth() - 2;
                     $('.ui-autocomplete.ui-menu').width(width);
                 });
            },
        }).autocomplete('widget').addClass('category-autocomplete');
        // fix space between input and confirm-button
        $('form.category .icon-confirm').insertAfter('#category');

        $timeout(function() {
            $('#category').focus();
                $('#category').autocomplete('search', '');
        });
    };
    $scope.closeCategory = function() {
        $scope.editCategory = false;
        var category = $('#category').val();
        if($scope.note.category !== category) {
            $scope.note.category = category;
            $scope.note.unsaved = true;
            $scope.autoSave($scope.note);
        }
    };

    $document.unbind('keypress.notes.save');
    $document.bind('keypress.notes.save', function(event) {
        if(event.ctrlKey || event.metaKey) {
            switch(String.fromCharCode(event.which).toLowerCase()) {
                case 's':
                    event.preventDefault();
                    $scope.manualSave();
                    break;
            }
        }
    });

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

    $scope.$watch(function() {
        return $scope.note.title;
    }, function(newValue) {
        if(newValue) {
            document.title = newValue + ' - ' + $scope.defaultTitle;
        } else {
            document.title = $scope.defaultTitle;
        }
    });

});
