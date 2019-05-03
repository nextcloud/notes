import AppGlobal from './mixins/AppGlobal'
import store from './store'
import axios from 'nextcloud-axios'

export default {

	tn: AppGlobal.methods.tn,

	url(url) {
		url = `/apps/notes${url}`
		return OC.generateUrl(url)
	},

	handleSyncError(message) {
		OC.Notification.showTemporary(message + ' ' + this.tn('See JavaScript console for details.'))
	},

	setSettings(settings) {
		return axios
			.put(this.url('/settings'), settings)
			.then(response => {
				let settings = response.data
				store.commit('setSettings', settings)
				return settings
			})
			.catch(err => {
				console.error(err)
				this.handleSyncError(this.tn('Updating settings has failed.'))
				throw err
			})
	},

	fetchNotes() {
		return axios
			.get(this.url('/notes'))
			.then(response => {
				store.commit('setSettings', response.data.settings)
				store.dispatch('addAll', response.data.notes)
				if (response.data.errorMessage) {
					OC.Notification.showTemporary(response.data.errorMessage)
				}
				return response.data
			})
			.catch(err => {
				console.error(err)
				this.handleSyncError(this.tn('Fetching notes has failed.'))
				throw err
			})
	},

	fetchNote(noteId) {
		return axios
			.get(this.url('/notes/' + noteId))
			.then(response => {
				let localNote = store.getters.getNote(parseInt(noteId))
				// only overwrite if there are no unsaved changes
				if (!localNote || !localNote.unsaved) {
					store.commit('add', response.data)
				}
				return response.data
			})
			.catch(err => {
				if (err.response.status === 404) {
					throw err
				} else {
					console.error(err)
					let msg = this.tn('Fetching note {id} has failed.', { id: noteId })
					store.commit('setNoteAttribute', { noteId: noteId, attribute: 'error', value: true })
					store.commit('setNoteAttribute', { noteId: noteId, attribute: 'errorMessage', value: msg })
					return store.getter.getNote(noteId)
				}
			})
	},

	createNote(category) {
		return axios
			.post(this.url('/notes'), { category: category })
			.then(response => {
				store.commit('add', response.data)
				return response.data
			})
			.catch(err => {
				console.error(err)
				this.handleSyncError(this.tn('Creating new note has failed.'))
				throw err
			})
	},

	_updateNote(note) {
		return axios
			.put(this.url('/notes/' + note.id), { content: note.content })
			.then(response => {
				let updated = response.data
				note.saveError = false
				note.title = updated.title
				note.modified = updated.modified
				if (updated.content === note.content) {
					note.unsaved = false
				}
				store.commit('add', note)
				return note
			})
			.catch(err => {
				store.commit('setNoteAttribute', { noteId: note.id, attribute: 'saveError', value: true })
				console.error(err)
				this.handleSyncError(this.tn('Updating note {id} has failed.', { id: note.id }))
			})
	},

	deleteNote(noteId) {
		return axios
			.delete(this.url('/notes/' + noteId))
			.then(() => {
				store.commit('remove', noteId)
			})
			.catch(err => {
				console.error(err)
				this.handleSyncError(this.tn('Deleting note {id} has failed.', { id: noteId }))
				throw err
			})
	},

	setFavorite(noteId, favorite) {
		return axios
			.put(this.url('/notes/' + noteId + '/favorite'), { favorite: favorite })
			.then(response => {
				store.commit('setNoteAttribute', { noteId: noteId, attribute: 'favorite', value: response.data })
			})
			.catch(err => {
				console.error(err)
				this.handleSyncError(this.tn('Toggling favorite for note {id} has failed.', { id: noteId }))
				throw err
			})
	},

	setCategory(noteId, category) {
		return axios
			.put(this.url('/notes/' + noteId + '/category'), { category: category })
			.then(response => {
				let realCategory = response.data
				if (category !== realCategory) {
					this.handleSyncError(this.tn('Updating the note\'s category has failed. Is the target directory writable?'))
				}
				store.commit('setNoteAttribute', { noteId: noteId, attribute: 'category', value: realCategory })
			})
			.catch(err => {
				console.error(err)
				this.handleSyncError(this.tn('Updating the category for note {id} has failed.', { id: noteId }))
				throw err
			})
	},

	saveNote(noteId, manualSave = false) {
		store.commit('addUnsaved', noteId)
		if (manualSave) {
			store.commit('setManualSave', true)
		}
		this._saveNotes()
	},
	_saveNotes() {
		let unsaved = store.state.unsaved
		let keys = Object.keys(unsaved)
		if (store.state.isSaving || keys.length === 0) {
			return
		}
		store.commit('setSaving', true)
		let promises = []
		for (let i = 0; i < keys.length; i++) {
			let note = unsaved[keys[i]]
			promises.push(this._updateNote(note))
		}
		store.commit('clearUnsaved')
		Promise.all(promises).finally(() => {
			store.commit('setSaving', false)
			store.commit('setManualSave', false)
			this._saveNotes()
		})
	},

	saveNoteManually(noteId) {
		store.commit('setNoteAttribute', { noteId: noteId, attribute: 'saveError', value: false })
		this.saveNote(noteId, true)
	},

	noteExists(noteId) {
		return store.getters.noteExists(noteId)
	},

	getCategories(maxLevel, details) {
		return store.getters.getCategories(maxLevel, details)
	},

	categoryLabel(category) {
		return category === '' ? this.tn('Uncategorized') : category.replace(/\//g, ' / ')
	},
}
