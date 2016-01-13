/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

/**
 * Like ng-change only that it does not fire when you type faster than
 * 300 ms
 */
app.directive('notesTimeoutChange', function ($timeout) {
    'use strict';

    return {
        restrict: 'A',
        link: function (scope, element, attributes) {
            var interval = 300;  // 300 miliseconds timeout after typing
            var timeout;

            $(element).bind('input propertychange paste', function () {
                $timeout.cancel(timeout);

                timeout = $timeout(function () {
                    scope.$apply(attributes.notesTimeoutChange);
                }, interval);
            });
        }
    };
});
