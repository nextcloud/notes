import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)


function nthIndexOf(str, pattern, n) {
	var i = -1;
	while (n-- && i++ < str.length) {
		i = str.indexOf(pattern, i);
		if (i < 0) {
			break;
		}
	}
	return i;
}


export default new Vuex.Store({
	state: {
		notes: []
	},
	mutations: {
		updateNotes(state, notes) {
			state.notes.length = 0
			state.notes.push.apply(state.notes, notes)
		},
	},
	getters: {
		getCategories: (state) => (maxLevel, details) => {
			var categories = {};
			var notes = state.notes;
			for(var i=0; i<notes.length; i+=1) {
				var cat = notes[i].category;
				if(maxLevel>0) {
					var index = nthIndexOf(cat, '/', maxLevel);
					if(index>0) {
						cat = cat.substring(0, index);
					}
				}
				if(categories[cat]===undefined) {
					categories[cat] = 1;
				} else {
					categories[cat] += 1;
				}
			}
			var result = [];
			for(var category in categories) {
				if(details) {
					result.push({
						name: category,
						count: categories[category],
					});
				} else if(category) {
					result.push(category);
				}
			}
			if(details) {
				result.sort(function (a, b) {
					return (a.name).localeCompare(b.name);
				})
			} else {
				result.sort();
			}
			return result;
		},
	},
})

