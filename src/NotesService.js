import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import AppGlobal from './mixins/AppGlobal'
import store from './store'

const t = AppGlobal.methods.t

function url(url) {
	url = `apps/notes${url}`
	return generateUrl(url)
}

function handleSyncError(message) {
	showError(message + ' ' + t('notes', 'See JavaScript console and server log for details.'))
}

function handleInsufficientStorage() {
	showError(t('notes', 'Saving the note has failed due to insufficient storage.'))
}

export const setSettings = settings => {
	return axios
		.put(url('/settings'), settings)
		.then(response => {
			const settings = response.data
			store.commit('setSettings', settings)
			return settings
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Updating settings has failed.'))
			throw err
		})
}

export const fetchNotes = () => {
	const lastETag = store.state.sync.etag
	const lastModified = store.state.sync.lastModified
	const headers = {}
	if (lastETag) {
		headers['If-None-Match'] = lastETag
	}
	return axios
		.get(
			url('/notes' + (lastModified ? '?pruneBefore=' + lastModified : '')),
			{ headers }
		)
		.then(response => {
			store.commit('setSettings', response.data.settings)
			store.commit('setCategories', response.data.categories)
			if (response.data.notes !== null) {
				store.dispatch('updateNotes', response.data.notes)
			}
			if (response.data.errorMessage) {
				showError(response.data.errorMessage)
			}
			store.commit('setSyncETag', response.headers['etag'])
			store.commit('setSyncLastModified', response.headers['last-modified'])
			return response.data
		})
		.catch(err => {
			if (err.response && err.response.status === 304) {
				store.commit('setSyncLastModified', err.response.headers['last-modified'])
				return null
			} else {
				console.error(err)
				handleSyncError(t('notes', 'Fetching notes has failed.'))
				throw err
			}
		})
}

export const fetchNote = noteId => {
	return axios
		.get(url('/notes/' + noteId))
		.then(response => {
			const localNote = store.getters.getNote(parseInt(noteId))
			// only overwrite if there are no unsaved changes
			if (!localNote || !localNote.unsaved) {
				store.commit('updateNote', response.data)
			}
			return response.data
		})
		.catch(err => {
			if (err.response.status === 404) {
				throw err
			} else {
				console.error(err)
				const msg = t('notes', 'Fetching note {id} has failed.', { id: noteId })
				store.commit('setNoteAttribute', { noteId: noteId, attribute: 'error', value: true })
				store.commit('setNoteAttribute', { noteId: noteId, attribute: 'errorMessage', value: msg })
				return store.getter.getNote(noteId)
			}
		})
}

export const setTitle = (noteId, title) => {
	return axios
		.put(url('/notes/' + noteId + '/title'), { title: title })
		.then(response => {
			store.commit('setNoteAttribute', { noteId: noteId, attribute: 'title', value: response.data })
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Renaming note {id} has failed.', { id: noteId }))
			throw err
		})
}

export const createNote = category => {
	return axios
		.post(url('/notes'), { category: category })
		.then(response => {
			store.commit('updateNote', response.data)
			return response.data
		})
		.catch(err => {
			console.error(err)
			if (err.response.status === 507) {
				handleInsufficientStorage()
			} else {
				handleSyncError(t('notes', 'Creating new note has failed.'))
			}
			throw err
		})
}

function _updateNote(note) {
	return axios
		.put(url('/notes/' + note.id), { content: note.content, autotitle: note.autotitle })
		.then(response => {
			const updated = response.data
			note.saveError = false
			note.title = updated.title
			note.modified = updated.modified
			if (updated.content === note.content) {
				note.unsaved = false
			}
			store.commit('updateNote', note)
			return note
		})
		.catch(err => {
			store.commit('setNoteAttribute', { noteId: note.id, attribute: 'saveError', value: true })
			console.error(err)
			if (err.response.status === 507) {
				handleInsufficientStorage()
			} else {
				handleSyncError(t('notes', 'Updating note {id} has failed.', { id: note.id }))
			}
		})
}

export const undoDeleteNote = (note) => {
	return axios
		.post(url('/notes/undo'), note)
		.then(response => {
			store.commit('updateNote', response.data)
			return response.data
		})
		.catch(err => {
			console.error(err)
			if (err.response.status === 507) {
				handleInsufficientStorage()
			} else {
				handleSyncError(t('notes', 'Undo delete has failed for note {title}.', { title: note.title }))
			}
			throw err
		})
}

export const deleteNote = noteId => {
	store.commit('setNoteAttribute', { noteId: noteId, attribute: 'deleting', value: 'deleting' })
	return axios
		.delete(url('/notes/' + noteId))
		.then(() => {
			store.commit('removeNote', noteId)
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Deleting note {id} has failed.', { id: noteId }))
			// remove note always since we don't know when the error happened
			store.commit('removeNote', noteId)
			throw err
		})
		.then(() => {
		})
}

export const setFavorite = (noteId, favorite) => {
	return axios
		.put(url('/notes/' + noteId + '/favorite'), { favorite: favorite })
		.then(response => {
			store.commit('setNoteAttribute', { noteId: noteId, attribute: 'favorite', value: response.data })
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Toggling favorite for note {id} has failed.', { id: noteId }))
			throw err
		})
}

export const setCategory = (noteId, category) => {
	return axios
		.put(url('/notes/' + noteId + '/category'), { category: category })
		.then(response => {
			const realCategory = response.data
			if (category !== realCategory) {
				handleSyncError(t('notes', 'Updating the note\'s category has failed. Is the target directory writable?'))
			}
			store.commit('setNoteAttribute', { noteId: noteId, attribute: 'category', value: realCategory })
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Updating the category for note {id} has failed.', { id: noteId }))
			throw err
		})
}

export const saveNote = (noteId, manualSave = false) => {
	store.commit('addUnsaved', noteId)
	if (manualSave) {
		store.commit('setManualSave', true)
	}
	_saveNotes()
}

function _saveNotes() {
	const unsavedNotes = Object.values(store.state.notes.unsaved)
	if (store.state.app.isSaving || unsavedNotes.length === 0) {
		return
	}
	store.commit('setSaving', true)
	const promises = unsavedNotes.map(note => _updateNote(note))
	store.commit('clearUnsaved')
	Promise.all(promises).then(() => {
		store.commit('setSaving', false)
		store.commit('setManualSave', false)
		_saveNotes()
	})
}

export const saveNoteManually = (noteId) => {
	store.commit('setNoteAttribute', { noteId: noteId, attribute: 'saveError', value: false })
	saveNote(noteId, true)
}

export const noteExists = (noteId) => {
	return store.getters.noteExists(noteId)
}

export const getCategories = (maxLevel, details) => {
	const categories = store.getters.getCategories(maxLevel, details)
	if (maxLevel === 0) {
		return [...new Set([...categories, ...store.state.notes.categories])]
	} else {
		return categories
	}
}

export const categoryLabel = (category) => {
	return category === '' ? t('notes', 'Uncategorized') : category.replace(/\//g, ' / ')
}

export const routeIsNewNote = ($route) => {
	return {}.hasOwnProperty.call($route.query, 'new')
}
