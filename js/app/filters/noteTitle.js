/**
 * removes whitespaces and leading #
 */
app.filter('noteTitle', function () {
	'use strict';
	return function (value) {
        	value = value.split('\n')[0] || 'newNote';
		return value.trim().replace(/^#+/g, '');
	};
});
