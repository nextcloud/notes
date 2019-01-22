import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

function nthIndexOf(str, pattern, n) {
	let i = -1
	while (n-- && i++ < str.length) {
		i = str.indexOf(pattern, i)
		if (i < 0) {
			break
		}
	}
	return i
}

export default new Vuex.Store({
	state: {
		notes: [],
		loaded: false,
	},
	mutations: {
		updateNotes(state, notes) {
			state.notes.length = 0
			state.notes.push.apply(state.notes, notes)
			state.loaded = true
		},
	},
	getters: {
		getCategories: (state) => (maxLevel, details) => {
			let categories = {}
			let notes = state.notes
			for (let i = 0; i < notes.length; i += 1) {
				let cat = notes[i].category
				if (maxLevel > 0) {
					let index = nthIndexOf(cat, '/', maxLevel)
					if (index > 0) {
						cat = cat.substring(0, index)
					}
				}
				if (categories[cat] === undefined) {
					categories[cat] = 1
				} else {
					categories[cat] += 1
				}
			}
			let result = []
			for (let category in categories) {
				if (details) {
					result.push({
						name: category,
						count: categories[category],
					})
				} else if (category) {
					result.push(category)
				}
			}
			if (details) {
				result.sort(function(a, b) {
					return (a.name).localeCompare(b.name)
				})
			} else {
				result.sort()
			}
			return result
		},
	},
})
