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
		return axios.get(this.url('/notes/' + noteId))
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

	getCategories(maxLevel, details) {
		return store.getters.getCategories(maxLevel, details)
	},

	categoryLabel(category) {
		return category === '' ? t('notes', 'Uncategorized') : category.replace(/\//g, ' / ')
	},
}
