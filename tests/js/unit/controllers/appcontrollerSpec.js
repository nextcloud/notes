/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


describe('AppController', function() {

	var controller,
		scope;

	// use the Notes container
	beforeEach(module('Notes'));

	beforeEach(inject(function ($controller, $rootScope) {
		scope = $rootScope.$new();
		controller = $controller;
	}));


	it('should bind loading global to scope', function () {
		var is = 'test';

		controller('AppController', {
			$scope: scope,
			is: is
		});

		expect(scope.is).toBe(is);
	});


});