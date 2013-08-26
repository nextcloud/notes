/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

/**
 * Binds translated values to scope and hides the element
 */
app.directive('notesTranslate', function () {
	return {
		restrict: 'E',
		link: function (scope, element, attributes) {
			var $element = $(element);
			$element.hide();
			scope.translations = scope.translations || {};
			scope.translations[attributes.key] = $element.text();
		}
	};
});
