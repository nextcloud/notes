/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page, TestInfo } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { login } from '../support/login.ts'
import { createNote, newNoteButton, noteRow, uniqueTitle } from '../support/note.ts'

async function openNoteActions(page: Page, noteId: number): Promise<Locator> {
	const row = noteRow(page, noteId)
	await row.hover()
	await row.locator('.action-item__menutoggle').click()
	return row
}

test.describe('Note actions', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
		await page.goto('/index.php/apps/notes/')
		await expect(newNoteButton(page)).toBeVisible()
	})

	test('toggles favorite from the actions menu', async ({ page }, testInfo: TestInfo) => {
		const title = uniqueTitle('favorite', testInfo)
		const noteId = await createNote(page, title)

		await openNoteActions(page, noteId)
		await page.getByRole('menuitem', { name: 'Add to favorites' }).click()

		await openNoteActions(page, noteId)
		await expect(page.getByRole('menuitem', { name: 'Remove from favorites' })).toBeVisible()
		await page.keyboard.press('Escape')

		await openNoteActions(page, noteId)
		await page.getByRole('menuitem', { name: 'Remove from favorites' }).click()

		await openNoteActions(page, noteId)
		await expect(page.getByRole('menuitem', { name: 'Add to favorites' })).toBeVisible()
	})

	test('renames a note from the actions menu', async ({ page }, testInfo: TestInfo) => {
		const title = uniqueTitle('rename', testInfo)
		const renamedTitle = `${title} renamed`
		const noteId = await createNote(page, title)

		await openNoteActions(page, noteId)
		await page.getByRole('menuitem', { name: 'Rename' }).click()

		const renameInput = page.getByRole('dialog', { name: 'Actions' }).getByRole('textbox')
		await expect(renameInput).toBeVisible()
		await renameInput.fill(renamedTitle)
		await renameInput.press('Enter')

		await expect(noteRow(page, noteId)).toContainText(renamedTitle)
	})

	test('deletes a note and undoes the deletion', async ({ page }, testInfo: TestInfo) => {
		const title = uniqueTitle('delete', testInfo)
		const autotitleSettled = page.waitForResponse((response) => response.url().includes('/autotitle')).catch(() => null)
		const noteId = await createNote(page, title)
		await autotitleSettled

		await openNoteActions(page, noteId)
		await page.getByRole('menuitem', { name: 'Delete note' }).click()

		await expect(noteRow(page, noteId)).toHaveCount(0)

		const undoButton = page.getByRole('button', { name: 'Undo Delete', exact: true })
		await expect(undoButton).toBeVisible()

		const undoRequest = page.waitForResponse((response) => response.url().includes('/notes/undo') && response.request().method() === 'POST')
		await undoButton.click()
		await undoRequest

		await expect(page.getByRole('link', { name: title })).toBeVisible()
	})
})
