/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

app.controller('AppController', ['$scope', '$location', 'is',
	function ($scope, $location, is) {
	$scope.is = is;

	$scope.init = function (lastViewedNote) {
		if(lastViewedNote !== 0) {
			$location.path('/notes/' + lastViewedNote);
		}
	};
}]);