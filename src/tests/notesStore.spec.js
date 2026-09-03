/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useNotesStore } from '../stores/notes.js'

describe('notes store updateNote', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useNotesStore()
		store.updateNote({ id: 1, title: 'A note', category: '', internalPath: '/Notes/A note.md' })
	})

	it('takes the path a moved note reports', () => {
		store.updateNote({ id: 1, category: 'Work', internalPath: '/Notes/Work/A note.md' })

		expect(store.getNote(1).internalPath).toBe('/Notes/Work/A note.md')
	})

	it('keeps the path when an update does not carry one', () => {
		store.updateNote({ id: 1, title: 'Renamed' })

		expect(store.getNote(1).internalPath).toBe('/Notes/A note.md')
		expect(store.getNote(1).title).toBe('Renamed')
	})
})
