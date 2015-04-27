/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

app.directive('markdown', function () {
	'use strict';

	marked.setOptions({
		sanitize: true,
		gfm: true,
		tables: true,
		breaks: true,
		smartLists: true,
		highlight: function (code) {
			return hljs.highlightAuto(code).value;
		}
	});
	return {
		restrict: 'AE',
		link: function (scope, element, attrs) {
			if (attrs.markdown) {
				scope.$watch(attrs.markdown, function (newVal) {
					var html = marked(newVal);
					element.html(html);
				});
			} else {
				var html = marked(element.text());
				element.html(html);
			}
		}
	};
});
