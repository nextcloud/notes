/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

describe('NoteController', function() {
    'use strict';

    var controller,
        scope,
        model,
        routeParams,
        note,
        SaveQueue;

    // use the Notes container
    beforeEach(module('Notes'));


    beforeEach(inject(function ($controller, $rootScope, NotesModel) {
        scope = $rootScope.$new();
        routeParams = {
            noteId: 3
        };
        note = {
            id: 3,
            title: 'yo',
            content: 'hi im here\nand this is a line'
        };
        model = NotesModel;

        SaveQueue = {
            add: jasmine.createSpy('add')
        };

        controller = $controller('NoteController', {
            $routeParams: routeParams,
            $scope: scope,
            NotesModel: model,
            SaveQueue: SaveQueue,
            note: note
        });

    }));


    it('should bind the correct note on scope', function () {
        expect(scope.note.title).toBe(note.title);
    });


    it ('should set the first line as title on save', function() {
        scope.updateTitle();
        expect(note.title).toBe('hi im here');
    });


    it ('should add the saved note to the save queue', function() {
        scope.save();
        expect(SaveQueue.add).toHaveBeenCalledWith(scope.note);
    });


    it ('should use new note if content is empty', function() {
        scope.note.content = '';
        scope.updateTitle();
        expect(note.title).toBe('New note');
    });

});