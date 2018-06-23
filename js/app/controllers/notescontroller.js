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

    $scope.folderSelectorOpen = false;
    $scope.filterCategory = null;

    $scope.orderRecent = ['-favorite','-modified'];
    $scope.orderAlpha = ['category','-favorite','title'];
    $scope.filterOrder = $scope.orderRecent;

    var notesResource = Restangular.all('notes');

    // initial request for getting all notes
    notesResource.getList().then(function (notes) {
        NotesModel.addAll(notes);
        $scope.notesLoaded = true;
    });

    $scope.create = function () {
        notesResource.post({category: $scope.filterCategory})
                     .then(function (note) {
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

    $scope.getCategories = _.memoize(function (notes) {
        return NotesModel.getCategories(notes, 1, true);
    });

    $scope.toggleFolderSelector = function () {
        $scope.folderSelectorOpen = !$scope.folderSelectorOpen;
    };

    $scope.setFilter = function (category) {
        if(category===null) {
            $scope.filterOrder = $scope.orderRecent;
        } else {
            $scope.filterOrder = $scope.orderAlpha;
        }
        $scope.filterCategory = category;
        $scope.folderSelectorOpen = false;
        $('#app-navigation > ul').animate({scrollTop: 0}, 'fast');
    };

    $scope.categoryFilter = function (note) {
        if($scope.filterCategory!==null) {
            if(note.category===$scope.filterCategory) {
                return true;
            } else if(note.category!==null) {
                return note.category.startsWith($scope.filterCategory+'/');
            }
        }
        return true;
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
