/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import logger from '../Logger.js'
import { copyNote } from '../Util.js'
import { useAppStore } from './app.js'

export const useNotesStore = defineStore('notes', {
	state: () => ({
		categories: [],
		localCategories: [],
		notes: [],
		notesIds: {},
		selectedCategory: null,
		selectedNote: null,
		filterString: '',
	}),

	getters: {
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

			function normalizeCategory(category) {
				let cat = category
				if (maxLevel > 0) {
					const index = nthIndexOf(cat, '/', maxLevel)
					if (index > 0) {
						cat = cat.substring(0, index)
					}
				}
				return cat
			}

			// get categories from notes
			const categories = {}
			for (const note of state.notes) {
				const cat = normalizeCategory(note.category)
				if (categories[cat] === undefined) {
					categories[cat] = 1
				} else {
					categories[cat] += 1
				}
			}
			const extraCategories = new Set([...state.categories, ...state.localCategories])
			for (const category of extraCategories) {
				if (!category) {
					continue
				}
				const cat = normalizeCategory(category)
				if (!cat) {
					continue
				}
				if (categories[cat] === undefined) {
					categories[cat] = 0
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
			const appStore = useAppStore()
			const searchText = appStore.searchText.toLowerCase()
			const notes = state.notes.filter((note) => {
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
				if (a.favorite && !b.favorite) {
					return -1
				}
				if (!a.favorite && b.favorite) {
					return 1
				}
				return b.modified - a.modified
			}

			function cmpCategory(a, b) {
				const cmpCat = a.category.localeCompare(b.category)
				if (cmpCat !== 0) {
					return cmpCat
				}
				if (a.favorite && !b.favorite) {
					return -1
				}
				if (!a.favorite && b.favorite) {
					return 1
				}
				return a.title.localeCompare(b.title)
			}

			notes.sort(state.selectedCategory === null ? cmpRecent : cmpCategory)

			return notes
		},

		getFilteredTotalCount: (state) => () => {
			const appStore = useAppStore()
			const searchText = appStore.searchText.toLowerCase()

			if (state.selectedCategory === null || searchText === '') {
				return 0
			}

			const notes = state.notes.filter((note) => {
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
	},

	actions: {
		updateNote(updated) {
			const note = this.notesIds[updated.id]
			if (note) {
				copyNote(updated, note, ['id', 'etag', 'content'])
				// don't update meta-data over full data
				if (updated.content !== undefined && updated.etag !== undefined) {
					note.content = updated.content
					note.etag = updated.etag
					note.unsaved = updated.unsaved
					note.error = updated.error
					note.errorType = updated.errorType
				}
			} else {
				this.notes.push(updated)
				this.notesIds[updated.id] = updated
			}
		},

		setNoteAttribute({ noteId, attribute, value }) {
			const note = this.notesIds[noteId]
			if (note) {
				note[attribute] = value
			}
		},

		removeNote(id) {
			this.notes = this.notes.filter((note) => note.id !== id)
			delete this.notesIds[id]
		},

		removeAllNotes() {
			this.notes = []
			this.notesIds = {}
		},

		setCategories(categories) {
			this.categories = categories
			categories.forEach((category) => {
				if (category && !this.localCategories.includes(category)) {
					this.localCategories.push(category)
				}
			})
		},

		addLocalCategory(category) {
			if (!category || this.localCategories.includes(category) || this.categories.includes(category)) {
				return
			}
			this.localCategories.push(category)
		},

		renameLocalCategory({ oldCategory, newCategory }) {
			if (!oldCategory || !newCategory || oldCategory === newCategory) {
				return
			}
			this.localCategories = this.localCategories.map((category) => {
				if (category === oldCategory) {
					return newCategory
				}
				if (category.startsWith(oldCategory + '/')) {
					return newCategory + category.slice(oldCategory.length)
				}
				return category
			})
			this.categories = this.categories.map((category) => {
				if (category === oldCategory) {
					return newCategory
				}
				if (category.startsWith(oldCategory + '/')) {
					return newCategory + category.slice(oldCategory.length)
				}
				return category
			})
		},

		removeLocalCategory(category) {
			if (!category) {
				return
			}
			this.localCategories = this.localCategories.filter((cat) => cat !== category && !cat.startsWith(category + '/'))
			this.categories = this.categories.filter((cat) => cat !== category && !cat.startsWith(category + '/'))
		},

		setSelectedCategory(category) {
			this.selectedCategory = category
		},

		setSelectedNote(note) {
			this.selectedNote = note
		},

		updateNotes({ noteIds, notes }) {
			// add/update new notes
			if (!notes || !noteIds) {
				// TODO remove this block after fixing #886
				logger.error('This should not happen, please see issue #886', { notes, noteIds })
				// eslint-disable-next-line no-console
				console.trace()
				return
			}
			for (const note of notes) {
				// TODO check for parallel (local) changes!
				this.updateNote(note)
			}
			// remove deleted notes
			this.notes.forEach((note) => {
				if (!noteIds.includes(note.id)) {
					this.removeNote(note.id)
				}
			})
		},
	},
})
