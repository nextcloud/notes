import store from './store'
import axios from 'nextcloud-axios'

export default {

	url(url) {
		url = `/apps/notes${url}`
		return OC.generateUrl(url)
	},

	fetchNotes() {
		return axios
			.get(this.url('/notes'))
			.then(response => {
				store.dispatch('addAll', response.data.notes)
				if (response.data.errorMessage) {
					OC.Notification.showTemporary(response.data.errorMessage)
				}
				return response.data
			})
			.catch(err => {
				console.error(err)
				// TODO error handling
			})
	},

	fetchNote(noteId) {
		return axios
			.get(this.url('/notes/' + noteId))
			.then(response => {
				store.commit('add', response.data)
				return response.data
			})
			.catch(err => {
				console.error(err)
				// TODO error handling
			})
	},

	noteExists(noteId) {
		return store.getters.noteExists(noteId)
	},

	createNote(category) {
		return axios
			.post(this.url('/notes'), { category: category })
			.then(response => {
				store.commit('add', response.data)
				return response.data
			})
	},

	updateNote(note) {
		return axios
			.put(this.url('/notes/' + note.id), { content: note.content })
			.then(response => {
				let updated = response.data
				note.error = false
				note.title = updated.title
				note.modified = updated.modified
				if (updated.content === note.content) {
					note.unsaved = false
				}
				store.commit('add', note)
				return note
			})
			.catch(err => {
				console.error(err)
				note.error = true
				// TODO error handling
			})
	},

	deleteNote(noteId) {
		return axios
			.delete(this.url('/notes/' + noteId))
			.then(() => {
				store.commit('remove', noteId)
			})
	},

	setFavorite(noteId, favorite) {
		return axios
			.put(this.url('/notes/' + noteId + '/favorite'), { favorite: favorite })
			.then(response => {
				let note = store.getters.getNote(noteId)
				note.favorite = response.data
				store.commit('add', note)
			})
			.catch(err => {
				console.error(err)
				// TODO error handling
			})
	},

	setCategory(noteId, category) {
		return axios
			.put(this.url('/notes/' + noteId + '/category'), { category: category })
			.then(response => {
				let realCategory = response.data
				if (category !== realCategory) {
					OC.Notification.showTemporary(
						t('notes', 'Updating the note\'s category has failed. Is the target directory writable?')
					)
				}
				let note = store.getters.getNote(noteId)
				note.category = realCategory
				store.commit('add', note) // TODO Einzel-Attribute aktualisieren in einem commit
			})
			.catch(err => {
				console.error(err)
				// TODO error handling
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
			promises.push(this.updateNote(note))
		}
		store.commit('clearUnsaved')
		Promise.all(promises).finally(() => {
			store.commit('setSaving', false)
			store.commit('setManualSave', false)
			this._saveNotes()
		})
	},

	getCategories(maxLevel, details) {
		return store.getters.getCategories(maxLevel, details)
	},

	categoryLabel(category) {
		return category === '' ? t('notes', 'Uncategorized') : category.replace(/\//g, ' / ')
	},
}
