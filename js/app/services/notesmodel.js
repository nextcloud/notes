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
            if(this.notesIds[id].error) {
                OC.Notification.show(
                    this.notesIds[id].errorMessage,
                    { type: 'error' }
                );
                return false;
            }
            return this.notesIds[id];
        },
        updateIfExists: function(updated) {
            var note = this.notesIds[updated.id];
            if(angular.isDefined(note)) {
                // don't update meta-data over full data
                if(updated.content !== null || note.content === null) {
                    note.title = updated.title;
                    note.modified = updated.modified;
                    note.content = updated.content;
                    note.favorite = updated.favorite;
                    note.category = updated.category;
                    note.error = updated.error;
                    note.errorMessage = updated.errorMessage;
                }
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
        },

        nthIndexOf: function(str, pattern, n) {
            var i = -1;
            while (n-- && i++ < str.length) {
                i = str.indexOf(pattern, i);
                if (i < 0) {
                    break;
                }
             }
             return i;
         },

         getCategories: function (notes, maxLevel, details) {
             var categories = {};
             for(var i=0; i<notes.length; i+=1) {
                 var cat = notes[i].category;
                 if(maxLevel>0) {
                     var index = this.nthIndexOf(cat, '/', maxLevel);
                     if(index>0) {
                         cat = cat.substring(0, index);
                     }
                 }
                 if(categories[cat]===undefined) {
                     categories[cat] = 1;
                 } else {
                     categories[cat] += 1;
                 }
             }
             var result = [];
             for(var category in categories) {
                 if(details) {
                     result.push({
                         name: category,
                         count: categories[category],
                     });
                 } else if(category) {
                     result.push(category);
                 }
             }
             if(!details) {
                 result.sort();
             }
             return result;
         },

    };

    return new NotesModel();
});
