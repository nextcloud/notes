describe('and filter', function() {
	'use strict';

	var result;
	var $filter;

	beforeEach(module('Notes'));

	beforeEach(inject(function(_$filter_) {
		$filter = _$filter_;
	}));


	it ('should do nothing if search string is empty', function() {
		result = $filter('and')([], '');
		expect(result).toEqual([]);

		result = $filter('and')(['a', 'lot', 'of', 'strings'], '');
		expect(result).toEqual(['a', 'lot', 'of', 'strings']);
	});

	it ('should match single words', function() {
		result = $filter('and')(['a', 'ad', 'multiple words'], 'd');
		expect(result).toEqual(['ad', 'multiple words']);
	});

	it ('should math multiple words', function() {
		result = $filter('and')(['a b c', 'a c e', 'a d'], 'a c');
		expect(result).toEqual(['a b c', 'a c e']);
	});

	it ('should return nothing if nothing matches', function() {
		result = $filter('and')(['brown fox jumps over the lazy dog'], 'quick');
		expect(result).toEqual([]);
	});
});
