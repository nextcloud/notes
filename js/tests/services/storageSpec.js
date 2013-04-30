/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

describe('Storage', function() {

	// global injected objects
	var $httpBackend,
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
	beforeEach(inject(function(_$httpBackend_, Storage, NotesModel, $rootScope,
								Loading) {
		$httpBackend = _$httpBackend_; // use a mock backend
		storage = Storage;
		notesModel = NotesModel;
		loading = Loading;

		// generate a fake route when we get the expected route
		router.generate.andCallFake(function(route, params) {
			params = params || {};

			if(route === 'notes_get_all' || route === 'notes_create') {
				return '/notes';
			} else if(route === 'notes_get' || route === 'notes_delete' ||
				route === 'notes_update') {
				return '/notes/' + params.id;
			}

		});

	}));


	it ('should create a note', function() {
		// provide a fake return when the url is hit
		var serverResponse = {
			data: {
				notes: [
					{id: 3, title: 'test'}
				]
			}
		};
		$httpBackend.expectPOST('/notes').respond(200, serverResponse);

		// test the returned promise
		var ok = false;
		var notOk = false;
		storage.create().then(function() {
			ok = true;
		}, function() {
			notOk = true;
		});

		expect(loading.isLoading()).toBe(true);

		$httpBackend.flush();

		expect(notesModel.getById(3)).toBe(serverResponse.data.notes[0]);
		expect(loading.isLoading()).toBe(false);
		expect(ok).toBe(true);
		expect(notOk).toBe(false);
	});


	it ('should execute the failure callback when create failed', function() {
		// provide a fake return when the url is hit
		$httpBackend.expectPOST('/notes').respond(500, '');

		// test the returned promise
		var ok = false;
		var notOk = false;
		storage.create().then(function() {
			ok = true;
		}, function() {
			notOk = true;
		});

		$httpBackend.flush();

		expect(loading.isLoading()).toBe(false);
		expect(ok).toBe(false);
		expect(notOk).toBe(true);
	});


	it ('should update a note', function() {
		// provide a fake return when the url is hit
		var note = {
			id: 3,
			title: 'john'
		};
		var serverResponse = {
			data: {
				notes: [
					{id: 3, title: 'test'}
				]
			}
		};
		$httpBackend.expectPUT('/notes/3').respond(200, serverResponse);

		// test the returned promise
		var ok = false;
		var notOk = false;
		storage.update(note).then(function() {
			ok = true;
		}, function() {
			notOk = true;
		});

		$httpBackend.flush();

		expect(notesModel.getById(3).title).toBe(
			serverResponse.data.notes[0].title);
		expect(ok).toBe(true);
		expect(notOk).toBe(false);
	});


	it ('should not update the same note twice at the same time', function() {
		var first = false;
		var second = false;
		$httpBackend.expectPUT('/notes/3').respond(200, 'abc');

		storage.update({id: 3}).then(function(data) {
			console.log(data);
			console.log('hi');
			$httpBackend.expectPUT('/notes/3').respond(200, 'test');
			first = true;
		});
		storage.update({id: 3}).then(function() {
			console.log('executed');
			second = true;
		});


		$httpBackend.flush();


		expect(first).toBe(true);
		expect(second).toBe(false);

		$httpBackend.flush();

		expect(second).toBe(true);
	});


	it('should get all notes', function() {
		// provide a fake return when the url is hit
		var serverResponse = {
			data: {
				notes: [
					{id: 3, title: 'test'}
				]
			}
		};

		$httpBackend.expectGET('/notes?').respond(201, serverResponse);

		// test the returned promise
		var ok = false;
		var notOk = false;
		storage.getAll().then(function() {
			ok = true;
		}, function() {
			notOk = true;
		});

		// there should be a loading sign
		expect(loading.isLoading()).toBe(true);

		// now return the fake response and check if its published to the Notes 
		// model
		$httpBackend.flush();

		expect(notesModel.getById(3)).toBe(serverResponse.data.notes[0]);
		expect(loading.isLoading()).toBe(false);
		expect(ok).toBe(true);
		expect(notOk).toBe(false);
	});


	it('should not show a loading sign if get all failed', function() {
		$httpBackend.expectGET('/notes?').respond(500, '');

		var ok = false;
		var notOk = false;
		storage.getAll().then(function() {
			ok = true;
		}, function() {
			notOk = true;
		});

		// there should be a loading sign
		expect(loading.isLoading()).toBe(true);

		// now return the fake response and check if its published to the Notes 
		// model
		$httpBackend.flush();

		expect(loading.isLoading()).toBe(false);
		expect(ok).toBe(false);
		expect(notOk).toBe(true);
	});


	it('should get a note by id', function() {
		// provide a fake return when the route is hit
		var serverResponse = {
			data: {
				notes: [
					{id: 1, title: 'test'}
				]
			}
		};
		$httpBackend.expectGET('/notes/1?').respond(201, serverResponse);

		var ok = false;
		var notOk = false;
		storage.getById(1).then(function() {
			ok = true;
		}, function() {
			notOk = true;
		});

		// there should be a loading sign
		expect(loading.isLoading()).toBe(true);

		// now return the fake response and check if its published to the Notes 
		// model
		$httpBackend.flush();

		expect(notesModel.getById(1)).toBe(serverResponse.data.notes[0]);
		expect(loading.isLoading()).toBe(false);
		expect(ok).toBe(true);
		expect(notOk).toBe(false);
	});


	it('should not show a loading sign if get by id failed', function() {
		$httpBackend.expectGET('/notes/1?').respond(500, '');

		storage.getById(1);

		// there should be a loading sign
		expect(loading.isLoading()).toBe(true);

		// now return the fake response and check if its published to the Notes 
		// model
		$httpBackend.flush();

		expect(loading.isLoading()).toBe(false);
	});


	it ('should send a delete request', function() {
		$httpBackend.expectDELETE('/notes/1').respond(200, '');

		storage.deleteById(1);

		$httpBackend.flush();
	});


	afterEach(function() {
		$httpBackend.verifyNoOutstandingExpectation();
		$httpBackend.verifyNoOutstandingRequest();
	});


});