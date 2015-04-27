/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

describe('NotesModel', function() {
    'use strict';

    beforeEach(module('Notes'));


    it ('should be empty', inject(function(NotesModel) {
        expect(NotesModel.getAll().length).toBe(0);
    }));


    it ('should add a note', inject(function(NotesModel) {
        NotesModel.add({id: 3, title: 'hey'});
        expect(NotesModel.getAll().length).toBe(1);
        expect(NotesModel.getAll()[0].title).toBe('hey');
        expect(NotesModel.get(3).title).toBe('hey');
    }));


    it ('should add notes', inject(function(NotesModel) {
        var notes = [
            {id: 3, title: 'hey'},
            {id: 4, title: 'he y'}
        ];
        NotesModel.addAll(notes);
        expect(NotesModel.getAll().length).toBe(2);
        expect(NotesModel.get(3).title).toBe('hey');
        expect(NotesModel.get(4).title).toBe('he y');
    }));


    it ('should add note if update fails', inject(function(NotesModel) {
        NotesModel.updateIfExists({id: 3, title: 'hey'});
        expect(NotesModel.getAll().length).toBe(1);
        expect(NotesModel.getAll()[0].title).toBe('hey');
        expect(NotesModel.get(3).title).toBe('hey');
    }));


    it ('should add note if update fails', inject(function(NotesModel) {
        NotesModel.add({id: 3, title: 'hey'});
        NotesModel.updateIfExists({id: 3, title: 'ha'});
        expect(NotesModel.getAll().length).toBe(1);
        expect(NotesModel.getAll()[0].title).toBe('ha');
        expect(NotesModel.get(3).title).toBe('ha');
    }));


    it ('should remove a note', inject(function(NotesModel) {
        NotesModel.add({id: 3, title: 'hey'});
        NotesModel.add({id: 2, title: 'hey'});
        NotesModel.remove(3);
        expect(NotesModel.getAll().length).toBe(1);
        expect(NotesModel.get(3)).not.toBeDefined();
    }));


});