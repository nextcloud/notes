/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

/**
 * Like ng-change only that it does not fire when you type faster than
 * 300 ms
 */
app.directive('notesTooltip', [function () {
	return {
		restrict: 'A',
		link: function (scope, element, attributes) {
			element.tooltip();
		}
	};
}]);
