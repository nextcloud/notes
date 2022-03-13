import Vue from 'vue'
import Vuex, { Store } from 'vuex'

import app from './store/app'
import notes from './store/notes'
import sync from './store/sync'

Vue.use(Vuex)

export default new Store({
	modules: {
		app,
		notes,
		sync,
	},
})
