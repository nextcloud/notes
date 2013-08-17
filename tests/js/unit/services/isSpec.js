/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


describe('is', function() {

	beforeEach(module('Notes'));


	it ('should be set loading to false', inject(function(is) {
		expect(is.loading).toBe(false);
	}));


});