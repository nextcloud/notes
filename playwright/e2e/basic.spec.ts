/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, type Page, test } from '@playwright/test'
import { login } from '../support/login'
import { NoteEditor } from '../support/sections/NoteEditor'

function currentNoteId(page: Page): number | null {
	const match = page.url().match(/\/note\/(\d+)(?:\?.*)?$/)
	return match ? Number(match[1]) : null
}

async function waitForNoteRoute(page: Page, previousNoteId: number | null): Promise<number> {
	await expect.poll(() => currentNoteId(page)).not.toBe(previousNoteId)

	const noteId = currentNoteId(page)
	if (noteId === null || noteId === previousNoteId) {
		throw new Error('Expected to navigate to a note route')
	}

	return noteId
}

test.describe('Basic checks', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
	})

	test('Notes app is visible', async ({ page }) => {
		await page.goto('/index.php/apps/notes/')
		await expect(page).toHaveTitle(/Notes/)
	})

	test('Create note and type', async ({ page }) => {
		await page.goto('/index.php/apps/notes/')
		const previousNoteId = currentNoteId(page)
		const newNoteButton = page.getByRole('button', { name: 'New note', exact: true })
		await expect(newNoteButton).toBeVisible()
		await newNoteButton.click()
		await waitForNoteRoute(page, previousNoteId)

		const editor = new NoteEditor(page)
		await editor.type('Hello from Playwright')
		await editor.expectText('Hello from Playwright')
	})

	test('Open share sidebar from note actions', async ({ page }) => {
		await page.goto('/index.php/apps/notes/')
		const previousNoteId = currentNoteId(page)
		await page.getByRole('button', { name: 'New note', exact: true }).click()
		const noteId = await waitForNoteRoute(page, previousNoteId)

		const editor = new NoteEditor(page)
		await editor.type('Sharing sidebar smoke test')

		const noteLink = page.locator(`a[href$="/note/${noteId}"]`).first()
		await expect(noteLink).toBeVisible()
		await noteLink.hover()

		const noteItem = noteLink.locator('xpath=ancestor::li[1]')
		await noteItem.locator('.action-item__menutoggle').click()
		await page.getByRole('menuitem', { name: 'Share', exact: true }).click()

		await expect(page.locator('[data-cy-notes-share-sidebar]')).toBeVisible({ timeout: 15000 })
		await expect(page.getByText('Internal shares')).toBeVisible({ timeout: 15000 })
	})
})
