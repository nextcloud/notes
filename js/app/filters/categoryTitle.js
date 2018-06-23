app.filter('categoryTitle', function () {
	'use strict';
	return function (str) {
		if (str && (typeof str === 'string')) {
			return str.replace(/\//g, ' / ');
		} else {
			return '';
		}
	};
});
