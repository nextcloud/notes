/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

describe('Storage', function() {

	// global injected objects
	var $httpBackend,
		scope,
		router,
		storage,
		notesModel,
		loading;

	// use the notes container
	beforeEach(module('Notes'));


	// replace the ownCloud router with a dummy. This is how you replace
	// services in the container
	beforeEach(module(function($provide) {
		// always execute the registered loaded callback since routes are
		// loaded async in production. This is ownCloud specific
		router = {
			registerLoadedCallback: function(callback) {
				callback();
			},

			// use this for returning abitrary links
			generate: jasmine.createSpy('generate route')
		};

		$provide.value('Router', router);
	}));


	// get a new instance with the replaced values from the container
	beforeEach(inject(function($injector, Storage, NotesModel, $rootScope,
	                           Loading) {
		$httpBackend = $injector.get('$httpBackend'); // use a mock backend
		storage = Storage;
		notesModel = NotesModel;
		scope = $rootScope;
		loading = Loading;

		// generate a fake route when we get the expected route
		router.generate.andCallFake(function(route, params) {
			if(route === 'notes_get_all') {
				return '/notes';
			}
		});
	}));


	it('should get all notes', function() {
		// provide a fake return when the route is hit
		var serverResponse = {
			data: {
				notes: [
					{id: 3, title: 'test'}
				]
			}
		};
		$httpBackend.expectGET('/notes?').respond(201, serverResponse);

		storage.getAll();

		// there should be a loading sign
		expect(loading.isLoading()).toBe(true);

		// now return the fake response and check if its published to the Notes 
		// model
		$httpBackend.flush();

		expect(notesModel.getById(3)).toBe(serverResponse.data.notes[0]);
		expect(loading.isLoading()).toBe(false);
	});


	it('should not show a loading sign if get all failed', function() {
		$httpBackend.expectGET('/notes?').respond(500, '');

		storage.getAll();

		// there should be a loading sign
		expect(loading.isLoading()).toBe(true);

		// now return the fake response and check if its published to the Notes 
		// model
		$httpBackend.flush();

		expect(loading.isLoading()).toBe(false);
	});


	afterEach(function() {
		$httpBackend.verifyNoOutstandingExpectation();
		$httpBackend.verifyNoOutstandingRequest();
	});


});