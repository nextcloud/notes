/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

__webpack_nonce__ = btoa(OC.requestToken)
__webpack_public_path__ = OC.linkTo('notes', 'js/') // eslint-disable-line

document.addEventListener('DOMContentLoaded', () => {
	OCA.Dashboard.register('notes', async (el) => {
		const { createApp } = await import(/* webpackChunkName: "dashboard-lazy" */'vue')
		const { default: Dashboard } = await import(/* webpackChunkName: "dashboard-lazy" */'./components/Dashboard.vue')
		const app = createApp(Dashboard)
		app.mixin({ methods: { t, n } })
		app.mount(el)
	})
})
