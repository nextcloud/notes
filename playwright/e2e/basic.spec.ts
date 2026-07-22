/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '@playwright/test'
import { login } from '../support/login.ts'
import { currentNoteId, newNoteButton, waitForNoteRoute } from '../support/note.ts'
import { NoteEditor } from '../support/sections/NoteEditor.ts'

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
		await expect(newNoteButton(page)).toBeVisible()
		await newNoteButton(page).click()
		await waitForNoteRoute(page, previousNoteId)

		const editor = new NoteEditor(page)
		await editor.type('Hello from Playwright')
		await editor.expectText('Hello from Playwright')
	})

	test('Open share sidebar from note actions', async ({ page }) => {
		await page.goto('/index.php/apps/notes/')
		const previousNoteId = currentNoteId(page)
		await newNoteButton(page).click()
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

	test('persists note content after a reload', async ({ page }) => {
		await page.goto('/index.php/apps/notes/')
		const previousNoteId = currentNoteId(page)
		await newNoteButton(page).click()
		const noteId = await waitForNoteRoute(page, previousNoteId)

		const editor = new NoteEditor(page)
		const content = `Persistence check ${Date.now()}`

		const savedResponse = page.waitForResponse((response) => response.url().includes(`/notes/${noteId}`)
			&& response.request().method() === 'PUT')
		await editor.type(content)
		await savedResponse

		await page.reload()
		await expect(page).toHaveURL(new RegExp(`/note/${noteId}(\\?.*)?$`))
		await editor.expectText(content)
	})

	test('filters notes with the search field', async ({ page }) => {
		await page.goto('/index.php/apps/notes/')

		const uniqueWord = `Findme${Date.now()}`
		const previousNoteId = currentNoteId(page)
		await newNoteButton(page).click()
		const noteId = await waitForNoteRoute(page, previousNoteId)

		const editor = new NoteEditor(page)
		await editor.type(uniqueWord)
		await editor.expectText(uniqueWord)

		const noteLink = page.locator(`a[href$="/note/${noteId}"]`).first()

		const searchField = page.getByRole('textbox', { name: 'Search for notes', exact: true })
		await searchField.fill('this text matches no note at all')
		await expect(noteLink).toHaveCount(0)

		await searchField.fill(uniqueWord)
		await expect(noteLink).toBeVisible()

		await searchField.fill('')
		await expect(noteLink).toBeVisible()
	})
})
