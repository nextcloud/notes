/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page, TestInfo } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { login } from '../support/login.ts'
import { createNote, newNoteButton, openNoteActions, setNoteMode, uniqueTitle } from '../support/note.ts'

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

function versionsList(page: Page): Locator {
	return sidebar(page).locator('[data-files-versions-versions-list]')
}

function subname(page: Page): Locator {
	return sidebar(page).locator('.app-sidebar-header__subname')
}

async function openSidebarFromActions(page: Page, noteId: number, action: string): Promise<void> {
	await openNoteActions(page, noteId)
	await page.getByRole('menuitem', { name: action, exact: true }).click()
	await expect(sidebar(page)).toBeVisible({ timeout: 15000 })
}

test.describe('Note sidebar', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
		await page.goto('/index.php/apps/notes/')
		await expect(newNoteButton(page)).toBeVisible()
	})

	test('opens the versions tab from the actions menu', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('versions', testInfo))

		await openSidebarFromActions(page, noteId, 'Versions')

		await expect(tabButton(page, 'files_versions')).toHaveAttribute('aria-selected', 'true')
		await expect(versionsList(page)).toBeAttached({ timeout: 15000 })
	})

	test('shows the size, the modification date and the owner of the note', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('sidebar-subname', testInfo))

		await openSidebarFromActions(page, noteId, 'Share')

		await expect(subname(page)).toBeVisible({ timeout: 15000 })
		await expect(subname(page)).toContainText(/\d+(\.\d+)?\s?(B|KB|MB|GB)/)
		await expect(subname(page).locator('[data-timestamp]')).toBeVisible()
		await expect(subname(page).locator('.user-bubble__content')).toContainText('admin')
	})

	test('renders the allow-listed tabs only', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('sidebar-tabs', testInfo))

		await openSidebarFromActions(page, noteId, 'Share')

		await expect(tabButton(page, 'sharing')).toBeVisible()
		await expect(tabButton(page, 'files_versions')).toBeVisible()
		await expect(sidebar(page).getByRole('tab')).toHaveCount(2)
	})

	test('switches between the sharing and versions tabs', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('sidebar-switch', testInfo))

		await openSidebarFromActions(page, noteId, 'Share')
		await expect(page.getByText('Internal shares')).toBeVisible({ timeout: 15000 })

		await tabButton(page, 'files_versions').click()
		await expect(tabButton(page, 'files_versions')).toHaveAttribute('aria-selected', 'true')
		await expect(versionsList(page)).toBeAttached({ timeout: 15000 })

		await tabButton(page, 'sharing').click()
		await expect(tabButton(page, 'sharing')).toHaveAttribute('aria-selected', 'true')
		await expect(page.getByText('Internal shares')).toBeVisible()
	})

	test('fills the sharing icon only while its tab is active', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('sidebar-icons', testInfo))

		await openSidebarFromActions(page, noteId, 'Share')

		await expect(tabButton(page, 'sharing').locator('.share-variant-icon')).toBeVisible()
		await expect(tabButton(page, 'sharing').locator('.share-variant-outline-icon')).toHaveCount(0)

		await tabButton(page, 'files_versions').click()

		await expect(tabButton(page, 'sharing').locator('.share-variant-outline-icon')).toBeVisible()
		await expect(tabButton(page, 'sharing').locator('.share-variant-icon')).toHaveCount(0)
	})

	test('lines the tab icons up with each other', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('sidebar-align', testInfo))

		await openSidebarFromActions(page, noteId, 'Share')
		await expect(tabButton(page, 'files_versions')).toBeVisible()

		const icons = await page.evaluate(() => {
			const box = (id: string) => {
				const selector = `#tab-button-${id} :is(.icon-vue, .material-design-icon)`
				const { y, height } = document.querySelector(selector)!.getBoundingClientRect()
				return { y, height }
			}
			return { sharing: box('sharing'), versions: box('files_versions') }
		})

		expect(icons.versions.y).toBeCloseTo(icons.sharing.y, 0)
		expect(icons.versions.height).toBeCloseTo(icons.sharing.height, 0)
	})

	test('falls back to the first tab when the requested one is unavailable', async ({ page }, testInfo: TestInfo) => {
		const noteId = await createNote(page, uniqueTitle('sidebar-fallback', testInfo))

		await page.evaluate((id) => {
			(window as unknown as EventBusWindow)._nc_event_bus
				.emit('notes:sidebar:open', { noteId: id, tab: 'not-a-note-sidebar-tab' })
		}, noteId)

		await expect(sidebar(page)).toBeVisible({ timeout: 15000 })
		await expect(tabButton(page, 'sharing')).toHaveAttribute('aria-selected', 'true')
		await expect(page.getByText('Internal shares')).toBeVisible({ timeout: 15000 })
	})

	// The editor's own actions menu only exists in the markdown editor; the rich
	// editor brings its own menu bar.
	test.describe('markdown editor', () => {
		test.beforeEach(async ({ page, request }) => {
			await setNoteMode(request, 'edit')
			await page.reload()
		})

		test.afterEach(async ({ request }) => {
			await setNoteMode(request, 'rich')
		})

		test('opens the sidebar from the editor actions menu', async ({ page }, testInfo: TestInfo) => {
			await createNote(page, uniqueTitle('sidebar-editor-menu', testInfo))

			await page.locator('.action-buttons .action-item__menutoggle').first().click()
			await page.getByRole('menuitem', { name: 'Open sidebar', exact: true }).click()

			await expect(sidebar(page)).toBeVisible({ timeout: 15000 })
			await expect(tabButton(page, 'sharing')).toHaveAttribute('aria-selected', 'true')
			await expect(page.getByText('Internal shares')).toBeVisible({ timeout: 15000 })
		})
	})
})
