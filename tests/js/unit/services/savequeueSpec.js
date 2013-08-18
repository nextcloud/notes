/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


describe('SaveQueue', function() {

	var http,
		q;

	beforeEach(module('Notes'));

	beforeEach(inject(function ($httpBackend, $q) {
		http = $httpBackend;
		q = $q;
	}));

	it ('should sync a note', inject(function(SaveQueue) {
		var note = {
			id: 3,
			put: function () {
				return q.defer().promise;
			}
		};
		SaveQueue.add(note);
		expect().toBe();
	}));


	it ('should sync a note synchronously', inject(function(SaveQueue) {

	}));


	it ('should sync updated notes', inject(function(SaveQueue) {

	}));


	afterEach(function() {
		http.verifyNoOutstandingExpectation();
		http.verifyNoOutstandingRequest();
	});


});