/**
 * group notes by (sub) category
 */
app.filter('groupNotes', function () {
	'use strict';
	return function (items, category) {
		if(category) {
			var prevCat = null;
			for(var i=0; i<items.length; i+=1) {
				var note = items[i];
				if(prevCat !== null && prevCat !== note.category) {
					items.splice(i, 0,
						note.category.substring(category.length+1));
				}
				prevCat = note.category;
			}
			return items;
		} else {
			return items;
		}
	};
});
