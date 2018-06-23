/**
 * group notes by (sub) category
 */
app.filter('groupNotes', ['$filter', function () {
	'use strict';
	return _.memoize(function (notes, category) {
		if(category) {
			var items = [];
			var prevCat = null;
			for(var i=0; i<notes.length; i+=1) {
				var note = notes[i];
				if(prevCat !== null && prevCat !== note.category) {
					items.push({
						isCategory: true,
						title: note.category.substring(category.length+1),
					});
				}
				prevCat = note.category;
				items.push(note);
			}
			return items;
		} else {
			return notes;
		}
	});
}]);
