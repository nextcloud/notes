/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { NOTE_SIDEBAR_TAB_IDS, selectNoteSidebarTabs } from '../sidebarTabs.js'

const ids = (tabs, context) => selectNoteSidebarTabs(tabs, context).map((tab) => tab.id)

const node = { basename: 'A note.md' }

describe('selectNoteSidebarTabs', () => {
	it('keeps the tabs a note sidebar hosts', () => {
		expect(NOTE_SIDEBAR_TAB_IDS).toEqual(['sharing', 'files_versions'])
	})

	it('drops every tab that is not on the allow-list', () => {
		const tabs = [
			{ id: 'sharing' },
			{ id: 'activity' },
			{ id: 'files_versions' },
			{ id: 'comments' },
		]

		expect(ids(tabs)).toEqual(['sharing', 'files_versions'])
	})

	it.each([
		['nothing registered', []],
		['a registry that is not there yet', null],
		['entries without an id', [{}, null, undefined]],
	])('returns no tabs for %s', (_label, tabs) => {
		expect(ids(tabs)).toEqual([])
	})

	it('sorts by the order the registering apps asked for', () => {
		const tabs = [
			{ id: 'files_versions', order: 5 },
			{ id: 'sharing', order: 1 },
		]

		expect(ids(tabs)).toEqual(['sharing', 'files_versions'])
	})

	it('treats a missing order as zero', () => {
		const tabs = [
			{ id: 'files_versions', order: 1 },
			{ id: 'sharing' },
		]

		expect(ids(tabs)).toEqual(['sharing', 'files_versions'])
	})

	it('keeps a tab while the node it would judge is still loading', () => {
		const tabs = [{ id: 'files_versions', enabled: () => false }]

		expect(ids(tabs, { node: null })).toEqual(['files_versions'])
	})

	it('asks the tab once the node is there', () => {
		const enabled = vi.fn(() => true)
		const folder = { basename: 'Notes' }
		const view = { id: 'notes' }

		expect(ids([{ id: 'sharing', enabled }], { node, folder, view })).toEqual(['sharing'])
		expect(enabled).toHaveBeenCalledWith({ node, folder, view })
	})

	it('drops a tab that says it does not apply to the node', () => {
		const tabs = [
			{ id: 'sharing' },
			{ id: 'files_versions', enabled: () => false },
		]

		expect(ids(tabs, { node })).toEqual(['sharing'])
	})

	it('drops only the tab whose predicate throws', () => {
		const tabs = [
			{ id: 'sharing' },
			{ id: 'files_versions', enabled: () => { throw new Error('no node for you') } },
		]

		expect(ids(tabs, { node })).toEqual(['sharing'])
	})

	it('leaves the registry it was given alone', () => {
		const tabs = [
			{ id: 'files_versions', order: 5 },
			{ id: 'sharing', order: 1 },
		]

		selectNoteSidebarTabs(tabs, { node })

		expect(tabs.map((tab) => tab.id)).toEqual(['files_versions', 'sharing'])
	})
})
