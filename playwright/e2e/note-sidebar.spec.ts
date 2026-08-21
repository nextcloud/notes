/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page, TestInfo } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { login } from '../support/login.ts'
import { createNote, newNoteButton, noteRow, uniqueTitle } from '../support/note.ts'

interface EventBusWindow extends Window {
	_nc_event_bus: {
		emit: (name: string, payload: unknown) => void
	}
}

function sidebar(page: Page): Locator {
	return page.locator('[data-cy-notes-share-sidebar]')
}

function tabButton(page: Page, tabId: string): Locator {
	return sidebar(page).locator(`#tab-button-${tabId}`)
}

function detailRow(page: Page, label: string): Locator {
	return sidebar(page).locator('.note-info__row')
		.filter({ has: page.getByText(label, { exact: true }) })
		.locator('.note-info__value')
}

/**
 * The store only holds a note's body once it has been saved, and the reading
 * estimate counts what the store holds, so the tests wait for the write.
 */
async function createSavedNote(page: Page, content: string): Promise<number> {
	const saved = page.waitForResponse((response) => /\/notes\/\d+$/.test(response.url())
		&& response.request().method() === 'PUT')
	const noteId = await createNote(page, content)
	await saved

	return noteId
}

async function openSidebarFromActions(page: Page, noteId: number, action: string): Promise<void> {
	const row = noteRow(page, noteId)
	await row.hover()
	await row.locator('.action-item__menutoggle').click()
	await page.getByRole('menuitem', { name: action, exact: true }).click()
	await expect(sidebar(page)).toBeVisible({ timeout: 15000 })
}

test.describe('Note sidebar', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
		await page.goto('/index.php/apps/notes/')
		await expect(newNoteButton(page)).toBeVisible()
	})

	test('opens the details tab from the actions menu', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('sidebar-details', testInfo))

		await openSidebarFromActions(page, noteId, 'Details')

		await expect(tabButton(page, 'notes-info')).toHaveAttribute('aria-selected', 'true')
		await expect(detailRow(page, 'Category')).toHaveText('Uncategorized')
		await expect(detailRow(page, 'Path')).toContainText('.md')
	})

	test('keeps the sharing tab reachable next to the details one', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('sidebar-tabs', testInfo))

		await openSidebarFromActions(page, noteId, 'Share')

		await expect(tabButton(page, 'sharing')).toHaveAttribute('aria-selected', 'true')
		await expect(page.getByText('Internal shares')).toBeVisible({ timeout: 15000 })

		await tabButton(page, 'notes-info').click()
		await expect(detailRow(page, 'Category')).toBeVisible()
	})

	test('fills a tab icon only while its tab is active', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('sidebar-icons', testInfo))

		await openSidebarFromActions(page, noteId, 'Details')

		await expect(tabButton(page, 'notes-info').locator('.information-icon')).toBeVisible()
		await expect(tabButton(page, 'notes-info').locator('.information-outline-icon')).toHaveCount(0)
		await expect(tabButton(page, 'sharing').locator('.share-variant-outline-icon')).toBeVisible()
		await expect(tabButton(page, 'sharing').locator('.share-variant-icon')).toHaveCount(0)

		await tabButton(page, 'sharing').click()

		await expect(tabButton(page, 'sharing').locator('.share-variant-icon')).toBeVisible()
		await expect(tabButton(page, 'notes-info').locator('.information-outline-icon')).toBeVisible()
	})

	test('falls back to the details tab when the requested one is unavailable', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createSavedNote(page, `# ${uniqueTitle('sidebar-fallback', testInfo)}\n\nfour plain words here`)
		// a reload drops the body from the store, so the tab has to fetch it
		await page.goto('/index.php/apps/notes/')

		await page.evaluate((id) => {
			(window as unknown as EventBusWindow)._nc_event_bus
				.emit('notes:sidebar:open', { noteId: id, tab: 'not-a-note-sidebar-tab' })
		}, noteId)

		await expect(sidebar(page)).toBeVisible({ timeout: 15000 })
		await expect(tabButton(page, 'notes-info')).toHaveAttribute('aria-selected', 'true')
		// the fallback has to load the body too, not just render the tab
		await expect(detailRow(page, 'Reading time')).toHaveText('1 minute')
	})

	test('estimates the reading time from the note body', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createSavedNote(page, `# ${uniqueTitle('sidebar-reading', testInfo)}\n\nfour plain words here`)

		await openSidebarFromActions(page, noteId, 'Details')

		await expect(detailRow(page, 'Reading time')).toHaveText('1 minute')
	})

	test('loads the body of the note it moves to while another body is still on its way', async ({ page }, testInfo: TestInfo) => {
		const held = await createSavedNote(page, uniqueTitle('sidebar-held', testInfo))
		const wanted = await createSavedNote(page, `# ${uniqueTitle('sidebar-wanted', testInfo)}\n\nfour plain words here`)
		const opened = await createSavedNote(page, uniqueTitle('sidebar-opened', testInfo))

		// a third note carries the route, so the editor loads neither of the two
		// bodies the tab is after, and the reload drops them from the store
		await page.goto(`/index.php/apps/notes/note/${opened}`)

		// keep the first body on its way while the sidebar is sent to the second
		await page.route(`**/apps/notes/notes/${held}`, async (route) => {
			if (route.request().method() !== 'GET') {
				return route.continue()
			}
			await new Promise((resolve) => setTimeout(resolve, 5000))
			await route.continue()
		})

		await openSidebarFromActions(page, held, 'Details')
		await openSidebarFromActions(page, wanted, 'Details')

		await expect(detailRow(page, 'Reading time')).toHaveText('1 minute', { timeout: 15000 })
	})

	test('follows the note the list navigates to', async ({ page }, testInfo: TestInfo) => {
		const first = await createSavedNote(page, uniqueTitle('sidebar-first', testInfo))
		const second = await createSavedNote(page, uniqueTitle('sidebar-second', testInfo))

		// the app is on the second note, so the sidebar starts where the route is
		await openSidebarFromActions(page, second, 'Details')
		const shown = await detailRow(page, 'Path').textContent()

		await noteRow(page, first).getByRole('link').first().click()

		// each note has a path of its own, so a different one means the sidebar moved
		await expect(page).toHaveURL(new RegExp(`/note/${first}(\\?.*)?$`))
		await expect(detailRow(page, 'Path')).not.toHaveText(shown ?? '')
	})

	test('marks the reading time unavailable when the note body cannot be loaded', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createSavedNote(page, uniqueTitle('sidebar-unreadable', testInfo))

		// a reload drops the body from the store, so the tab has to fetch it
		await page.route(
			`**/apps/notes/notes/${noteId}`,
			(route) => route.request().method() === 'GET' ? route.abort() : route.continue(),
		)
		await page.goto('/index.php/apps/notes/')

		await openSidebarFromActions(page, noteId, 'Details')

		await expect(detailRow(page, 'Reading time')).toHaveText('—')
	})
})
