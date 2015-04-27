/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

describe('notesTimeoutChange', function() {
    'use strict';

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


    it ('should fire a change event on changed content after timeout',
    function () {
        var element = angular.element(
            '<input type="text" notes-timeout-change="changed=true"/>'
        );
        compile(element)(rootScope);
        rootScope.$digest();
        host.append(element);

        element.trigger('propertychange').val('ho');

        // no change before timeout
        expect(rootScope.changed).not.toBeDefined();

        timeout.flush();

        // now the timeout has been triggered and it should work
        expect(rootScope.changed).toBe(true);
    });


    it ('should reset the timeout if fast input happens', function () {
        var element = angular.element(
            '<input ng-init="counter=0"' +
            ' type="text" notes-timeout-change="counter=counter+1"/>'
        );
        compile(element)(rootScope);
        rootScope.$digest();
        host.append(element);

        element.trigger('propertychange').val('ho');
        element.trigger('propertychange').val('ho');

        // no change before timeout
        expect(rootScope.changed).not.toBeDefined();

        timeout.flush();

        // now the timeout has been triggered and it should work
        expect(rootScope.counter).toBe(1);
    });


    afterEach(function () {
        host.remove();
    });


});