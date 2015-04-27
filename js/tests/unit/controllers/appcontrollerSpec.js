/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

describe('AppController', function() {
    'use strict';

    var controller,
        scope,
        location;

    // use the Notes container
    beforeEach(module('Notes'));

    beforeEach(inject(function ($controller, $rootScope) {
        scope = $rootScope.$new();
        controller = $controller;
        location = {
            path: jasmine.createSpy('path')
        };
    }));


    it('should bind loading global to scope', function () {
        var is = 'test';

        controller('AppController', {
            $scope: scope,
            $location: location,
            is: is
        });

        expect(scope.is).toBe(is);
    });


    it('should redirect if last viewed note is not 0', function () {
        controller('AppController', {
            $scope: scope,
            $location: location
        });

        scope.init(3);
        expect(location.path).toHaveBeenCalledWith('/notes/3');

    });


    it('should not redirect if last viewed note is 0', function () {
        controller('AppController', {
            $scope: scope,
            $location: location
        });

        scope.init(0);
        expect(location.path).not.toHaveBeenCalled();

    });

});