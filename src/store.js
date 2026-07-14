/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'

import { useAppStore } from './stores/app.js'
import { useNotesStore } from './stores/notes.js'
import { useSyncStore } from './stores/sync.js'

export const pinia = createPinia()
setActivePinia(pinia)

export default {
	app: useAppStore(),
	notes: useNotesStore(),
	sync: useSyncStore(),
}
