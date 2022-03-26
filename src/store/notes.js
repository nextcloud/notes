import Vue from 'vue'
import { copyNote } from '../Util'

const state = {
	categories: [],
	notes: [],
	notesIds: {},
	selectedCategory: null,
}

const getters = {
	numNotes: (state) => () => {
		return state.notes.length
	},

	noteExists: (state) => (id) => {
		return state.notesIds[id] !== undefined
	},

	getNote: (state) => (id) => {
		if (state.notesIds[id] === undefined) {
			return null
		}
		return state.notesIds[id]
	},

	getCategories: (state) => (maxLevel, details) => {
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

		// get categories from notes
		const categories = {}
		for (const note of state.notes) {
			let cat = note.category
			if (maxLevel > 0) {
				const index = nthIndexOf(cat, '/', maxLevel)
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
		// get structured result from categories
		const result = []
		for (const category in categories) {
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
			result.sort((a, b) => a.name.localeCompare(b.name))
		} else {
			result.sort()
		}
		return result
	},

	getFilteredNotes: (state) => () => {
		const notes = state.notes.filter(note => {
			if (state.selectedCategory !== null
				&& state.selectedCategory !== note.category
				&& !note.category.startsWith(state.selectedCategory + '/')) {
				return false
			}
			return true
		})

		function cmpRecent(a, b) {
			if (a.favorite && !b.favorite) return -1
			if (!a.favorite && b.favorite) return 1
			return b.modified - a.modified
		}

		function cmpCategory(a, b) {
			const cmpCat = a.category.localeCompare(b.category)
			if (cmpCat !== 0) return cmpCat
			if (a.favorite && !b.favorite) return -1
			if (!a.favorite && b.favorite) return 1
			return a.title.localeCompare(b.title)
		}

		notes.sort(state.selectedCategory === null ? cmpRecent : cmpCategory)

		return notes
	},

	getSelectedCategory: (state) => () => {
		return state.selectedCategory
	},
}

const mutations = {
	updateNote(state, updated) {
		const note = state.notesIds[updated.id]
		if (note) {
			copyNote(updated, note, ['id', 'etag', 'content'])
			// don't update meta-data over full data
			if (updated.content !== undefined && updated.etag !== undefined) {
				note.content = updated.content
				note.etag = updated.etag
				Vue.set(note, 'unsaved', updated.unsaved)
				Vue.set(note, 'error', updated.error)
				Vue.set(note, 'errorType', updated.errorType)
			}
		} else {
			state.notes.push(updated)
			Vue.set(state.notesIds, updated.id, updated)
		}
	},

	setNoteAttribute(state, params) {
		const note = state.notesIds[params.noteId]
		if (note) {
			Vue.set(note, params.attribute, params.value)
		}
	},

	removeNote(state, id) {
		state.notes = state.notes.filter(note => note.id !== id)
		Vue.delete(state.notesIds, id)
	},

	removeAllNotes(state) {
		state.notes = []
		state.notesIds = {}
	},

	setCategories(state, categories) {
		state.categories = categories
	},

	setSelectedCategory(state, category) {
		state.selectedCategory = category
	},
}

const actions = {
	updateNotes(context, { noteIds, notes }) {
		// add/update new notes
		for (const note of notes) {
			// TODO check for parallel (local) changes!
			context.commit('updateNote', note)
		}
		// remove deleted notes
		context.state.notes.forEach(note => {
			if (!noteIds.includes(note.id)) {
				context.commit('removeNote', note.id)
			}
		})
	},
}

export default { state, getters, mutations, actions }
