/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { set } from 'vue'

const state = {
	queue: {},
	etag: null,
	lastModified: 0,
	active: false,
	// TODO add list of notes with changes during sync
}

const getters = {
}

const mutations = {
	addToQueue(state, { noteId, type }) {
		const cmd = { noteId, type }
		const key = noteId + '-' + type
		set(state.queue, key, cmd)
	},

	clearQueue(state) {
		state.queue = {}
	},

	setSyncETag(state, etag) {
		if (etag) {
			state.etag = etag
		}
	},

	setSyncLastModified(state, strLastModified) {
		const lastModified = Date.parse(strLastModified)
		if (lastModified) {
			state.lastModified = lastModified / 1000
		}
	},

	clearSyncCache(state) {
		state.etag = null
		state.lastModified = 0
	},

	setSyncActive(state, active) {
		state.active = active
	},
}

const actions = {
}

export default { state, getters, mutations, actions }
