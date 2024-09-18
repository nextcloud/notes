/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import App from './App.vue'
import router from './router.js'
import store from './store.js'

__webpack_nonce__ = btoa(OC.requestToken) // eslint-disable-line
__webpack_public_path__ = OC.linkTo('notes', 'js/') // eslint-disable-line

Vue.mixin({ methods: { t, n } })

// Make sure that the filesClient is available in the global scope used by the sidebar
// FIXME: Can be dropped once Nextcloud 28 is the minimum supported version
Object.assign(window.OCA.Files, { App: { fileList: { filesClient: OC.Files.getClient() } } }, window.OCA.Files)

export default new Vue({
	el: '#content',
	store,
	router,
	render: h => h(App),
})
