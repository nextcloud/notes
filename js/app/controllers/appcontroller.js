/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

app.controller('AppController', ['$scope', 'is',
	function ($scope, is) {
	$scope.is = is;
}]);