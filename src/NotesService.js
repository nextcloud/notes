import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import store from './store.js'
import { copyNote } from './Util.js'

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

export const deleteEditorMode = () => {
	return axios
		.post(url('/settings/migrate'))
		.catch(err => {
			console.error(err)
			throw err
		})
}

export const getDashboardData = () => {
	return axios
		.get(url('/notes/dashboard'))
		.then(response => {
			return response.data
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Fetching notes for dashboard has failed.'), err)
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
			if (response.data.categories) {
				store.commit('setCategories', response.data.categories)
			}
			if (response.data.noteIds && response.data.notesData) {
				store.dispatch('updateNotes', { noteIds: response.data.noteIds, notes: response.data.notesData })
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
			if (err?.response?.status === 304) {
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
				_updateLocalNote(response.data)
			}
			return response.data
		})
		.catch(err => {
			if (err?.response?.status === 404) {
				throw err
			} else {
				console.error(err)
				const msg = t('notes', 'Fetching note {id} has failed.', { id: noteId })
				store.commit('setNoteAttribute', { noteId, attribute: 'error', value: true })
				store.commit('setNoteAttribute', { noteId, attribute: 'errorType', value: msg })
				return store.getter.getNote(noteId)
			}
		})
}

export const refreshNote = (noteId, lastETag) => {
	const headers = {}
	if (lastETag) {
		headers['If-None-Match'] = lastETag
	}
	const note = store.getters.getNote(noteId)
	const oldContent = note.content
	return axios
		.get(
			url('/notes/' + noteId),
			{ headers }
		)
		.then(response => {
			if (note.conflict) {
				store.commit('setNoteAttribute', { noteId, attribute: 'conflict', value: response.data })
				return response.headers.etag
			}
			const currentContent = store.getters.getNote(noteId).content
			// only update if local content has not changed
			if (oldContent === currentContent) {
				_updateLocalNote(response.data)
				return response.headers.etag
			}
			return null
		})
		.catch(err => {
			if (err?.response?.status === 304 || note.deleting) {
				// ignore error if note is deleting or not changed
				return null
			} else if (err?.code === 'ECONNABORTED') {
				// ignore cancelled request
				console.debug('Refresh Note request was cancelled.')
				return null
			} else {
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

export const createNote = (category, title, content) => {
	return axios
		.post(url('/notes'), {
			category: category || '',
			content: content || '',
			title: title || '',
		})
		.then(response => {
			_updateLocalNote(response.data)
			return response.data
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Creating new note has failed.'), err)
			throw err
		})
}

function _updateLocalNote(note, reference) {
	if (reference === undefined) {
		reference = copyNote(note, {})
	}
	store.commit('updateNote', note)
	store.commit('setNoteAttribute', { noteId: note.id, attribute: 'reference', value: reference })
}

function _updateNote(note) {
	const requestOptions = { headers: { 'If-Match': '"' + note.etag + '"' } }
	return axios
		.put(url('/notes/' + note.id), { content: note.content }, requestOptions)
		.then(response => {
			note.saveError = false
			store.commit('setNoteAttribute', { noteId: note.id, attribute: 'conflict', value: undefined })
			const updated = response.data
			if (updated.content === note.content) {
				// everything is fine
				// => update note with remote data
				_updateLocalNote(
					{ ...updated, unsaved: false }
				)
			} else {
				// content has changed locally in the meanwhile
				// => merge note, but exclude content
				_updateLocalNote(
					copyNote(updated, note, ['content']),
					copyNote(updated, {})
				)
			}
		})
		.catch(err => {
			if (err?.response?.status === 412) {
				// ETag does not match, try to merge changes
				note.saveError = false
				store.commit('setNoteAttribute', { noteId: note.id, attribute: 'conflict', value: undefined })
				const reference = note.reference
				const remote = err.response.data
				if (remote.content === note.content) {
					// content is already up-to-date
					// => update note with remote data
					_updateLocalNote(
						{ ...remote, unsaved: false }
					)
				} else if (remote.content === reference.content) {
					// remote content has not changed
					// => use all other attributes and sync again
					_updateLocalNote(
						copyNote(remote, note, ['content']),
						copyNote(remote, {})
					)
					queueCommand(note.id, 'content')
				} else {
					console.info('Note update conflict. Manual resolution required.')
					store.commit('setNoteAttribute', { noteId: note.id, attribute: 'conflict', value: remote })
				}
			} else {
				store.commit('setNoteAttribute', { noteId: note.id, attribute: 'saveError', value: true })
				console.error(err)
				handleSyncError(t('notes', 'Saving note {id} has failed.', { id: note.id }), err)
			}
		})
}

export const conflictSolutionLocal = note => {
	note.etag = note.conflict.etag
	_updateLocalNote(
		copyNote(note.conflict, note, ['content']),
		copyNote(note.conflict, {})
	)
	store.commit('setNoteAttribute', { noteId: note.id, attribute: 'conflict', value: undefined })
	queueCommand(note.id, 'content')
}

export const conflictSolutionRemote = note => {
	_updateLocalNote(
		{ ...note.conflict, unsaved: false }
	)
	store.commit('setNoteAttribute', { noteId: note.id, attribute: 'conflict', value: undefined })
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
			_updateLocalNote(response.data)
			return response.data
		})
		.catch(err => {
			console.error(err)
			handleSyncError(t('notes', 'Undo delete has failed for note {title}.', { title: note.title }), err)
			throw err
		})
}

export const deleteNote = async (noteId, onNoteDeleted) => {
	store.commit('setNoteAttribute', { noteId, attribute: 'deleting', value: 'deleting' })
	try {
		await axios.delete(url('/notes/' + noteId))
	} catch (err) {
		console.error(err)
		handleSyncError(t('notes', 'Deleting note {id} has failed.', { id: noteId }), err)
	}
	// remove note always since we don't know when exactly the error happened
	// (note could be deleted on server even if an error was thrown)
	onNoteDeleted()
	store.commit('removeNote', noteId)
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

export const queueCommand = (noteId, type) => {
	store.commit('addToQueue', { noteId, type })
	_processQueue()
}

function _processQueue() {
	const queue = Object.values(store.state.sync.queue)
	if (store.state.app.isSaving || queue.length === 0) {
		return
	}
	store.commit('setSaving', true)
	store.commit('clearQueue')

	async function _executeQueueCommands() {
		for (const cmd of queue) {
			try {
				switch (cmd.type) {
				case 'content':
					await _updateNote(store.state.notes.notesIds[cmd.noteId])
					break
				case 'autotitle':
					await autotitleNote(cmd.noteId)
					break
				default:
					console.error('Unknown queue command: ' + cmd.type)
				}

			} catch (e) {
				console.error('Command has failed with error:')
				console.error(e)
			}
		}
		store.commit('setSaving', false)
		store.commit('setManualSave', false)
		_processQueue()
	}
	_executeQueueCommands()
}

export const saveNoteManually = (noteId) => {
	store.commit('setNoteAttribute', { noteId, attribute: 'saveError', value: false })
	store.commit('setManualSave', true)
	queueCommand(noteId, 'content')
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
