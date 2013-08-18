/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


describe('notesTimeoutChange', function() {

	var host,
		rootScope,
		compile,
		timeout;

	beforeEach(module('Notes'));

	beforeEach(inject(function($rootScope, $compile, $timeout) {
		rootScope = $rootScope;
		compile = $compile;
		timeout = $timeout;
		host = $('<div id="host"></div>');
		$('body').append(host);

	}));


	it ('should fire a change event on changed content after timeout', function () {
		var element = angular.element(
			'<input type="text" notes-timeout-change="changed=true"/>'
		);
		compile(element)(rootScope);
		rootScope.$digest();
		host.append(element);

		element.trigger('keypress').val('ho');

		// no change before timeout
		expect(rootScope.changed).not.toBeDefined();

		timeout.flush();

		// now the timeout has been triggered and it should work
		expect(rootScope.changed).toBe(true);
	});


	afterEach(function () {
		host.remove();
	});

});