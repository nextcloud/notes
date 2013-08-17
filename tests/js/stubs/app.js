/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

var app = angular.module('Notes', ['ngMock', 'restangular']).
config(['RestangularProvider', function (RestangularProvider) {
	RestangularProvider.setBaseUrl('/');
}]);