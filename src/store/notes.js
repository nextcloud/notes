import Vue from 'vue'

const state = {
	categories: [],
	notes: [],
	notesIds: {},
	unsaved: {},
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
}

const mutations = {
	updateNote(state, updated) {
		const note = state.notesIds[updated.id]
		if (note) {
			// don't update meta-data over full data
			if (updated.content !== undefined || note.content === undefined) {
				note.title = updated.title
				note.modified = updated.modified
				note.content = updated.content
				note.favorite = updated.favorite
				note.category = updated.category
				Vue.set(note, 'autotitle', updated.autotitle)
				Vue.set(note, 'unsaved', updated.unsaved)
				Vue.set(note, 'error', updated.error)
				Vue.set(note, 'errorMessage', updated.errorMessage)
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
		const index = state.notes.findIndex(note => note.id === id)
		if (index !== -1) {
			state.notes.splice(index, 1)
			delete state.notesIds[id]
		}
	},

	removeAllNotes(state) {
		state.notes = []
		state.notesIds = {}
	},

	addUnsaved(state, id) {
		Vue.set(state.unsaved, id, state.notesIds[id])
	},

	clearUnsaved(state) {
		state.unsaved = {}
	},

	setCategories(state, categories) {
		state.categories = categories
	},
}

const actions = {
	updateNotes(context, notes) {
		const noteIds = {}
		// add/update new notes
		for (const note of notes) {
			noteIds[note.id] = true
			// TODO check for parallel (local) changes!
			// only update, if note has changes (see API "purgeBefore")
			if (note.title !== undefined) {
				context.commit('updateNote', note)
			}
		}
		// remove deleted notes
		context.state.notes.forEach(note => {
			if (noteIds[note.id] === undefined) {
				context.commit('removeNote', note.id)
			}
		})
	},
}

export default { state, getters, mutations, actions }
