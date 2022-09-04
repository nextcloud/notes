import Vue, { set } from 'vue'
import { copyNote } from '../Util.js'

const state = {
	categories: [],
	notes: [],
	notesIds: {},
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
			copyNote(updated, note, ['id', 'etag', 'content'])
			// don't update meta-data over full data
			if (updated.content !== undefined && updated.etag !== undefined) {
				note.content = updated.content
				note.etag = updated.etag
				set(note, 'unsaved', updated.unsaved)
				set(note, 'error', updated.error)
				set(note, 'errorType', updated.errorType)
			}
		} else {
			state.notes.push(updated)
			set(state.notesIds, updated.id, updated)
		}
	},

	setNoteAttribute(state, params) {
		const note = state.notesIds[params.noteId]
		if (note) {
			set(note, params.attribute, params.value)
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
}

const actions = {
	updateNotes(context, { noteIds, notes }) {
		// add/update new notes
		if (!notes || !noteIds) {
			// TODO remove this block after fixing #886
			console.error('This should not happen, please see issue #886')
			console.info(notes)
			console.info(noteIds)
			// eslint-disable-next-line no-console
			console.trace()
			return
		}
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
