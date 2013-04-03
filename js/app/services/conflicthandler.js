/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

// take care of fileconflicts by appending a number
app.factory('conflictHandler', ['NotesModel', function(NotesModel){

	var handler = function(title){
		if(NotesModel.getByTitle(title).length > 0){
			var previousTitle = title;

			// count up number
			title = title.replace(/\((\d+)\)$/ig, function(match, number){
				var nextNumber = parseInt(number, 10) + 1;
				return '(' + nextNumber + ')';
			});

			// if title does not contain (NUMBER) add it
			if(title === previousTitle){
				title += ' (2)';
			}

			return handler(title);
		} else {
			return title;
		}
	};

	return handler;
}]);
