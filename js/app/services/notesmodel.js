/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

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
                note.favorite = updated.favorite;
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
