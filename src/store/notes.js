/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue, { set } from 'vue'
import { copyNote } from '../Util.js'

const state = {
	categories: [],
	categoryStats: null, // Category counts from backend (set on first load)
	totalNotesCount: null, // Total number of notes from backend (set on first load)
	notes: [],
	notesIds: {},
	selectedCategory: null,
	selectedNote: null,
	filterString: '',
	notesLoadingInProgress: false,
}

const getters = {
	numNotes: (state) => () => {
		// Use total count from backend if available, otherwise fall back to loaded notes count
		return state.totalNotesCount !== null ? state.totalNotesCount : state.notes.length
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

		// Use backend category stats if available (set on first load)
		// Otherwise calculate from loaded notes (partial data during pagination)
		let categories = {}
		if (state.categoryStats) {
			// Use pre-calculated stats from backend
			categories = { ...state.categoryStats }
			// Apply maxLevel filtering if needed
			if (maxLevel > 0) {
				const filteredCategories = {}
				for (const cat in categories) {
					const index = nthIndexOf(cat, '/', maxLevel)
					const truncatedCat = index > 0 ? cat.substring(0, index) : cat
					if (filteredCategories[truncatedCat] === undefined) {
						filteredCategories[truncatedCat] = categories[cat]
					} else {
						filteredCategories[truncatedCat] += categories[cat]
					}
				}
				categories = filteredCategories
			}
		} else {
			// Fallback: calculate from loaded notes (may be incomplete during pagination)
			for (const note of state.notes) {
				// Skip invalid notes
				if (!note || !note.category) {
					continue
				}
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

	getFilteredNotes: (state, getters, rootState, rootGetters) => () => {
		const searchText = rootState.app.searchText?.toLowerCase() || ''
		const notes = state.notes.filter(note => {
			// Skip invalid notes
			if (!note || !note.category || !note.title) {
				return false
			}

			if (state.selectedCategory !== null
				&& state.selectedCategory !== note.category
				&& !note.category.startsWith(state.selectedCategory + '/')) {
				return false
			}

			if (searchText !== '' && note.title.toLowerCase().indexOf(searchText) === -1) {
				return false
			}

			return true
		})

		function cmpRecent(a, b) {
			// Defensive: ensure both notes are valid
			if (!a || !b) return 0
			if (a.favorite && !b.favorite) return -1
			if (!a.favorite && b.favorite) return 1
			return (b.modified || 0) - (a.modified || 0)
		}

		function cmpCategory(a, b) {
			// Defensive: ensure both notes are valid
			if (!a || !b || !a.category || !b.category || !a.title || !b.title) return 0
			const cmpCat = a.category.localeCompare(b.category)
			if (cmpCat !== 0) return cmpCat
			if (a.favorite && !b.favorite) return -1
			if (!a.favorite && b.favorite) return 1
			return a.title.localeCompare(b.title)
		}

		notes.sort(state.selectedCategory === null ? cmpRecent : cmpCategory)

		return notes
	},

	getFilteredTotalCount: (state, getters, rootState, rootGetters) => () => {
		const searchText = rootState.app.searchText?.toLowerCase() || ''

		if (state.selectedCategory === null || searchText === '') {
			return 0
		}

		const notes = state.notes.filter(note => {
			// Skip invalid notes
			if (!note || !note.category || !note.title) {
				return false
			}

			if (state.selectedCategory === note.category || note.category.startsWith(state.selectedCategory + '/')) {
				return false
			}

			if (note.title.toLowerCase().indexOf(searchText) === -1) {
				return false
			}

			return true
		})
		return notes.length
	},

	getSelectedCategory: (state) => () => {
		return state.selectedCategory
	},

	getSelectedNote: (state) => () => {
		return state.selectedNote
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
		state.categoryStats = null
		state.totalNotesCount = null
	},

	setCategories(state, categories) {
		state.categories = categories
	},

	setCategoryStats(state, stats) {
		state.categoryStats = stats
	},

	setTotalNotesCount(state, count) {
		state.totalNotesCount = count
	},

	setSelectedCategory(state, category) {
		state.selectedCategory = category
	},

	setSelectedNote(state, note) {
		state.selectedNote = note
	},

	setNotesLoadingInProgress(state, loading) {
		state.notesLoadingInProgress = loading
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

	updateNotesIncremental(context, { notes, isLastChunk }) {
		// Add/update notes from current chunk
		if (!notes) {
			return
		}
		for (const note of notes) {
			// TODO check for parallel (local) changes!
			context.commit('updateNote', note)
		}
		// Note: We don't remove deleted notes here - that's done in finalizeNotesUpdate
	},

	finalizeNotesUpdate(context, allNoteIds) {
		// Remove notes that are no longer on the server
		// This is only called after all chunks have been loaded
		if (!allNoteIds) {
			return
		}
		context.state.notes.forEach(note => {
			if (!allNoteIds.includes(note.id)) {
				context.commit('removeNote', note.id)
			}
		})
	},
}

export default { state, getters, mutations, actions }
