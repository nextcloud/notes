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
				var notes = response.data
				console.log(notes); // TODO remove log
				store.commit('updateNotes', notes);
			})
			.catch(err => {
				console.log(err);
				// TODO error handling
			});
	},

}
