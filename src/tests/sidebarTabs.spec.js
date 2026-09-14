/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { initializeSidebarTab, NOTE_SIDEBAR_TAB_IDS, selectNoteSidebarTabs, TAB_DEFINITION_TIMEOUT } from '../sidebarTabs.js'

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

describe('initializeSidebarTab', () => {
	let elements

	/**
	 * @param {string} tagName the custom element a tab brings
	 * @return {object} the registry entry standing in for that element
	 */
	function element(tagName) {
		if (!elements.has(tagName)) {
			let resolve
			const defined = new Promise((settle) => {
				resolve = settle
			})
			elements.set(tagName, { defined, resolve, isDefined: false })
		}
		return elements.get(tagName)
	}

	/**
	 * @param {string} tagName the custom element to report as defined
	 */
	function define(tagName) {
		const entry = element(tagName)
		entry.isDefined = true
		entry.resolve()
	}

	beforeEach(() => {
		elements = new Map()
		vi.spyOn(window.customElements, 'get')
			.mockImplementation((tagName) => (element(tagName).isDefined ? class {} : undefined))
		vi.spyOn(window.customElements, 'whenDefined')
			.mockImplementation((tagName) => element(tagName).defined)
	})

	afterEach(() => {
		vi.restoreAllMocks()
		vi.useRealTimers()
	})

	it('takes an element that is already in the registry as it is', async () => {
		const onInit = vi.fn()
		define('already-defined-tab')

		await expect(initializeSidebarTab({ id: 'sharing', tagName: 'already-defined-tab', onInit })).resolves.toBe(true)
		expect(onInit).not.toHaveBeenCalled()
	})

	it('waits for the element the tab promises to define', async () => {
		const onInit = vi.fn(async () => define('defining-tab'))

		await expect(initializeSidebarTab({ id: 'sharing', tagName: 'defining-tab', onInit })).resolves.toBe(true)
		expect(onInit).toHaveBeenCalledTimes(1)
	})

	it('initializes an element once while opens overlap', async () => {
		const onInit = vi.fn(async () => define('shared-tab'))
		const tab = { id: 'sharing', tagName: 'shared-tab', onInit }

		const [first, second] = await Promise.all([initializeSidebarTab(tab), initializeSidebarTab(tab)])

		expect([first, second]).toEqual([true, true])
		expect(onInit).toHaveBeenCalledTimes(1)
	})

	it('gives up on a tab whose initialization throws', async () => {
		const onInit = vi.fn(async () => {
			throw new Error('no element for you')
		})

		await expect(initializeSidebarTab({ id: 'sharing', tagName: 'throwing-tab', onInit })).resolves.toBe(false)
	})

	it('gives up on a tab that never defines its element', async () => {
		vi.useFakeTimers()
		const tab = { id: 'sharing', tagName: 'silent-tab', onInit: vi.fn() }

		const usable = initializeSidebarTab(tab)
		await vi.advanceTimersByTimeAsync(TAB_DEFINITION_TIMEOUT + 1)

		await expect(usable).resolves.toBe(false)
	})

	it('lets a tab that failed try again on the next open', async () => {
		const onInit = vi.fn()
			.mockImplementationOnce(async () => {
				throw new Error('not this time')
			})
			.mockImplementationOnce(async () => define('retried-tab'))
		const tab = { id: 'sharing', tagName: 'retried-tab', onInit }

		await expect(initializeSidebarTab(tab)).resolves.toBe(false)
		await expect(initializeSidebarTab(tab)).resolves.toBe(true)
		expect(onInit).toHaveBeenCalledTimes(2)
	})
})
