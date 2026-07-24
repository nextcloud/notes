/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import App from './App.vue'
import router from './router.js'

__webpack_nonce__ = btoa(OC.requestToken)
__webpack_public_path__ = OC.linkTo('notes', 'js/') // eslint-disable-line

const app = createApp(App)
app.mixin({ methods: { t, n } })
app.use(router)
app.mount('#content')
