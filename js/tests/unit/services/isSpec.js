/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

describe('is', function() {
    'use strict';

    beforeEach(module('Notes'));


    it ('should be set loading to false', inject(function(is) {
        expect(is.loading).toBe(false);
    }));


});