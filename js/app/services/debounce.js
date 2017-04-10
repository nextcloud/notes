/**
 * Copyright (c) 2016, Hendrik Leppelsack
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

app.factory('debounce', ['$timeout', function($timeout) {
	'use strict';

	return function debounce(func, delay) {
		var timeout;

		return function() {
			var context = this, args = arguments;

			if(timeout) {
				$timeout.cancel(timeout);
			}
			timeout = $timeout(function() {
				func.apply(context, args);
			}, delay);
		};
	};
}]);
