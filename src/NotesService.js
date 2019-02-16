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
				store.dispatch('addAll', response.data)
			})
			.catch(err => {
				console.error(err)
				// TODO error handling
			})
	},

	fetchNote(noteId) {
		return axios.get(this.url('/notes/' + noteId))
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

	categoryLabel(category) {
		return category === '' ? t('notes', 'Uncategorized') : category.replace(/\//g, ' / ')
	},
}
