/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

app.directive('notesTimeoutChange', ['$timeout', function ($timeout) {
	return {
		restrict: 'A',
		link: function (scope, element, attributes) {
			var interval = 300;  // 300 miliseconds timeout after typing
			var lastChange = new Date().getTime();
			var timeout;

			$(element).keyup(function () {
				var now = new Date().getTime();
				
				if(now - lastChange < interval) {
					$timeout.cancel(timeout);
				}

				timeout = $timeout(function () {
					scope.$apply(attributes.notesTimeoutChange);
				}, interval);

				lastChange = now;
			});
		}
	};
}]);
