/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { set } from 'vue'

export const useSyncStore = defineStore('sync', {
	state: () => ({
		queue: {},
		etag: null,
		lastModified: 0,
		active: false,
		// TODO add list of notes with changes during sync
	}),

	actions: {
		addToQueue({ noteId, type }) {
			const cmd = { noteId, type }
			const key = noteId + '-' + type
			set(this.queue, key, cmd)
		},

		clearQueue() {
			this.queue = {}
		},

		setSyncETag(etag) {
			if (etag) {
				this.etag = etag
			}
		},

		setSyncLastModified(strLastModified) {
			const lastModified = Date.parse(strLastModified)
			if (lastModified) {
				this.lastModified = lastModified / 1000
			}
		},

		clearSyncCache() {
			this.etag = null
			this.lastModified = 0
		},

		setSyncActive(active) {
			this.active = active
		},
	},
})
