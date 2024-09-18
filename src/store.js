/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Vuex, { Store } from 'vuex'

import app from './store/app.js'
import notes from './store/notes.js'
import sync from './store/sync.js'

Vue.use(Vuex)

export default new Store({
	modules: {
		app,
		notes,
		sync,
	},
})
