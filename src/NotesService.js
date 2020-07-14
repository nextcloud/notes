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

function handleSyncError(message, err = null) {
	if (err?.response) {
		const statusCode = err.response?.status
		switch (statusCode) {
		case 404:
			showError(message + ' ' + t('notes', 'Note not found.'))
			break
		case 423:
			showError(message + ' ' + t('notes', 'Note is locked.'))
			break
		case 507:
			showError(message + ' ' + t('notes', 'Insufficient storage.'))
			break
		default:
			showError(message + ' HTTP ' + statusCode + ' (' + err.response.data?.errorType + ')')
		}
	} else {
		showError(message + ' ' + t('notes', 'See JavaScript console and server log for details.'))
	}
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
			handleSyncError(t('notes', 'Updating settings has failed.'), err)
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
				showError(t('notes', 'Error from Nextcloud server: {msg}', { msg: response.data.errorMessage }))
			} else {
				store.commit('setSyncETag', response.headers.etag)
				store.commit('setSyncLastModified', response.headers['last-modified'])
			}
			return response.data
		})
		.catch(err => {
			if (err.response && err.response.status === 304) {
				store.commit('setSyncLastModified', err.response.headers['last-modified'])
				return null
			} else {
				console.error(err)
				handleSyncError(t('notes', 'Fetching notes has failed.'), err)
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
				store.commit('setNoteAttribute', { noteId, attribute: 'error', value: true })
				store.commit('setNoteAttribute', { noteId, attribute: 'errorMessage', value: msg })
				return store.getter.getNote(noteId)
			}
		})
}

export const refreshNote = (noteId, lastETag) => {
	const headers = {}
	if (lastETag) {
		headers['If-None-Match'] = lastETag
	}
	const oldContent = store.getters.getNote(noteId).content
	return axios
		.get(
			url('/notes/' + noteId),
			{ headers }
		)
		.then(response => {
			const currentContent = store.getters.getNote(noteId).content
			// only update if local content has not changed
			if (oldContent === currentContent) {
				store.commit('updateNote', response.data)
				return response.headers.etag
			}
			return null
		})
		.catch(err => {
			if (err.response.status !== 304) {
				console.error(err)
				handleSyncError(t('notes', 'Refreshing note {id} has failed.', { id: noteId }), err)
			}
			return null
		})
}

export const setTitle = (noteId, title) => {
	return axios
		.put(url('/notes/' + noteId + '/title'), { title })
		.then(response => {
			store.commit('setNoteAttribute', { noteId, attribute: 'title', value: response.data })
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Renaming note {id} has failed.', { id: noteId }), err)
			throw err
		})
}

export const createNote = category => {
	return axios
		.post(url('/notes'), { category })
		.then(response => {
			store.commit('updateNote', response.data)
			return response.data
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Creating new note has failed.'), err)
			throw err
		})
}

function _updateNote(note) {
	return axios
		.put(url('/notes/' + note.id), { content: note.content })
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
			handleSyncError(t('notes', 'Saving note {id} has failed.', { id: note.id }), err)
		})
}

export const autotitleNote = noteId => {
	return axios
		.put(url('/notes/' + noteId + '/autotitle'))
		.then((response) => {
			store.commit('setNoteAttribute', { noteId, attribute: 'title', value: response.data })
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Updating title for note {id} has failed.', { id: noteId }), err)
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
			handleSyncError(t('notes', 'Undo delete has failed for note {title}.', { title: note.title }), err)
			throw err
		})
}

export const deleteNote = noteId => {
	store.commit('setNoteAttribute', { noteId, attribute: 'deleting', value: 'deleting' })
	return axios
		.delete(url('/notes/' + noteId))
		.then(() => {
			store.commit('removeNote', noteId)
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Deleting note {id} has failed.', { id: noteId }), err)
			// remove note always since we don't know when the error happened
			store.commit('removeNote', noteId)
			throw err
		})
		.then(() => {
		})
}

export const setFavorite = (noteId, favorite) => {
	return axios
		.put(url('/notes/' + noteId + '/favorite'), { favorite })
		.then(response => {
			store.commit('setNoteAttribute', { noteId, attribute: 'favorite', value: response.data })
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Toggling favorite for note {id} has failed.', { id: noteId }), err)
			throw err
		})
}

export const setCategory = (noteId, category) => {
	return axios
		.put(url('/notes/' + noteId + '/category'), { category })
		.then(response => {
			const realCategory = response.data
			if (category !== realCategory) {
				handleSyncError(t('notes', 'Updating the note\'s category has failed. Is the target directory writable?'))
			}
			store.commit('setNoteAttribute', { noteId, attribute: 'category', value: realCategory })
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Updating the category for note {id} has failed.', { id: noteId }), err)
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
	store.commit('setNoteAttribute', { noteId, attribute: 'saveError', value: false })
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
