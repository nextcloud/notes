/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

app.controller('AppController', function ($scope, $location, is) {
    'use strict';

    $scope.is = is;

    $scope.init = function (lastViewedNote, errorMessage, useSearchAPI) {
        $scope.defaultTitle = document.title;

        if(lastViewedNote !== 0 && $location.path()==='') {
            $location.path('/notes/' + lastViewedNote);
        }
        if(errorMessage) {
            OC.Notification.showTemporary(errorMessage);
        }
        if(useSearchAPI) {
            $scope.initSearch();
        }
    };

    $scope.search = '';
    $scope.defaultTitle = null;

    $scope.initSearch = function() {
        new OCA.Search(
            function (query) {
                $scope.search = query;
                $scope.$apply();
                if($('#app-navigation-toggle').css('display')!=='none' &&
                        !$('body').hasClass('snapjs-left')) {
                    $('#app-navigation-toggle').click();
                }
            },
            function () {
                $scope.search = '';
                $scope.$apply();
            }
        );
    };

});
