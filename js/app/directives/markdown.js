/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

app.directive('markdown', function () {
	return {
		restrict: 'AE',
		link: function (scope, element, attrs) {
			if (attrs.markdown) {
				scope.$watch(attrs.markdown, function (newVal) {
					var html = markdown.toHTML(newVal);
					element.html(html);
				});
			} else {
				var html = markdown.toHTML(element.text());
				element.html(html);
			}
		}
	};
});
