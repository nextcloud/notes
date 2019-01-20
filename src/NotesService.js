import store from './store'
import axios from 'nextcloud-axios'

export default {
	url(url) {
		url = `/apps/notes${url}`
		return OC.generateUrl(url)
	},
	fetchNotes() {
		axios
			.get(this.url('/notes'))
			.then(response => {
				let notes = response.data
				console.debug(notes) // TODO remove log
				store.commit('updateNotes', notes)
			})
			.catch(err => {
				console.error(err)
				// TODO error handling
			})
	},

}
