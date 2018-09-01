/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

app.directive('notesTooltip', function () {
    'use strict';

    return {
        restrict: 'A',
        link: function (scope, element) {
            element.tooltip({'container': 'body'});

            element.on('$destroy', function() {
                element.tooltip('hide');
            });

            element.on('click', function() {
                element.tooltip('hide');
            });
        }
    };
});
