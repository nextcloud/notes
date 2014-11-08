/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


describe('Config', function() {

	beforeEach(module('Notes'));

	var http,
		config;

	beforeEach(inject(function (Config, $httpBackend) {
		config = Config;
		http = $httpBackend;
	}));

	it ('should be set proper initialize values', inject(function() {
		expect(config.isMarkdown()).toBe(false);
	}));


	it('should load the initial config', inject(function () {
		http.expectGET('/config').respond(200, {
			markdown: true
		});

		config.load();
		http.flush();

		expect(config.isMarkdown()).toBe(true);
	}));


	it('should sync values back to server', inject(function () {
		http.expectPOST('/config', {
			markdown: true
		}).respond(200);

		config.setIsMarkdown(true);
	
		config.sync();
	}));

});