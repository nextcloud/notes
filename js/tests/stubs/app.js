/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

/*jshint unused:false*/
var app = angular.module('Notes', ['ngMock', 'restangular', 'ngRoute']).
config(['RestangularProvider', function (RestangularProvider) {
    'use strict';
    RestangularProvider.setBaseUrl('/');
}]);