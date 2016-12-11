/**
 * filter by multiple words (AND operation)
 */
app.filter('and', ['$filter', function ($filter) {
	'use strict';
	return function (items, searchString) {
		var searchValues = searchString.split(' ');
		var filtered = items;
		for(var i in searchValues) {
			filtered = $filter('filter')(filtered, searchValues[i]);
		}
		return filtered;
	};
}]);
