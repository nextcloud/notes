/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

app.controller('NoteController', function($routeParams, $scope, NotesModel,
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
            $scope.translations['New note'];
    };

    $scope.save = function() {
        var note = $scope.note;
        SaveQueue.add(note);
    };

    $scope.sync = function (markdown) {
        Config.setIsMarkdown(markdown);
        Config.sync();
    };

});