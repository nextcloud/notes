/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

describe('conflictHandler', function() {

	var handler,
		model;

	beforeEach(module('Notes'));

	beforeEach(inject(function(conflictHandler, NotesModel) {
		handler = conflictHandler;
		model = NotesModel;
	}));


	it('should not rename title if there is no conflict', function() {
		var note = {
			title: 'hi'
		};
		model.add(note);

		expect(handler('test')).toBe('test');
	});


	it('should not rename title to title (2) if there is a conflict', function(){
		var note = {
			title: 'hi'
		};
		model.add(note);

		expect(handler('hi')).toBe('hi (2)');
	});


	it('should not rename title (2) to title (3) if there is a conflict', function(){
		var note = {
			title: 'hi (2)'
		};
		model.add(note);

		expect(handler('hi (2)')).toBe('hi (3)');
	});


	it('should not rename title (15) to title (16) if there is a conflict', function(){
		var note = {
			title: 'hi (15)'
		};
		model.add(note);

		expect(handler('hi (15)')).toBe('hi (16)');
	});


});