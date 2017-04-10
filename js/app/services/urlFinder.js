/**
 * Copyright (c) 2016, Hendrik Leppelsack
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

// finds the url which should be opened when a link is clicked
// example: '[hello](http://example.com)'
app.factory('urlFinder', [function() {
	'use strict';

	return function urlFinder(element) {
		element = $(element);

		// special case: click on ')'
		if(element.is('.cm-url.cm-formatting')) {
			if(element.prev().length !== 0) {
				element = element.prev();
			}
		}

		// skip '[hello]'
		while(element.is('.cm-link')) {
			element = element.next();
		}

		// skip '('
		while(element.is('.cm-url.cm-formatting')) {
			element = element.next();
		}

		// check if we actually have a cm-url
		if(element.is('.cm-url:not(.cm-formatting)')) {
			return element.text();
		}

		return undefined;
	};
}]);
