/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

app.directive('notesIsSaving', function ($window) {
    'use strict';
    return {
        restrict: 'A',
        scope: {
            'notesIsSaving': '='
        },
        link: function (scope) {
            $window.onbeforeunload = function () {
                if (scope.notesIsSaving) {
                    return t('notes', 'Note is currently saving. Leaving ' +
                                      'the page will delete all changes!');
                } else {
                    return null;
                }
            };
        }
    };
});
