/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

// take care of fileconflicts by appending a number
app.factory('NotesModel', function () {
	var NotesModel = function () {
		this.notes = [];
		this.notesIds = {};
	};

	NotesModel.prototype = {
		addAll: function (notes) {
			for(var i=0; i<notes.length; i++) {
				this.add(notes[i]);
			}
		},
		add: function(note) {
			this.notes.push(note);
			this.notesIds[note.id] = note;
		},
		getAll: function () {
			return this.notes;
		},
		get: function (id) {
			return this.notesIds[id];
		},
		update: function(updated) {
			var keys = Object.keys(updated);
			var note = this.notesIds[updated.id];

			for(var i=0; i<keys.length; i++) {
				var key = keys[i];
				
				if(key !== 'id') {
					note[key] = updated[key];
				}
			}
		}
	};

	return new NotesModel();
});