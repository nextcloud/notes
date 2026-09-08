/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page, TestInfo } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { login } from '../support/login.ts'
import { createNote, currentNoteId, newNoteButton, noteRow, uniqueTitle, waitForNoteRoute } from '../support/note.ts'

function appNavigation(page: Page): Locator {
	return page.getByRole('navigation').filter({
		has: page.getByRole('button', { name: 'New category', exact: true }),
	}).first()
}

function newCategoryButton(page: Page): Locator {
	return page.getByRole('button', { name: 'New category', exact: true })
}

function notesSearchField(page: Page): Locator {
	return page.getByRole('textbox', { name: 'Search for notes', exact: true })
}

function notesViewNewNoteButton(page: Page): Locator {
	return notesSearchField(page)
		.locator('xpath=ancestor::div[contains(@class, "content-list__search")][1]')
		.getByRole('button', { name: 'New note', exact: true })
}

function navigationRow(page: Page, name: string): Locator {
	return page.getByRole('link', { name, exact: true })
		.locator('xpath=ancestor::li[1]')
		.first()
}

function navigationLink(page: Page, name: string): Locator {
	return page.getByRole('link', { name, exact: true })
}

async function expectNavigationItemActive(page: Page, name: string): Promise<void> {
	await expect(navigationLink(page, name)).toHaveAttribute('aria-current', 'page')
}

async function openNotesApp(page: Page): Promise<void> {
	await page.goto('/index.php/apps/notes/')
	await expect(newCategoryButton(page)).toBeVisible()
	await expect(newNoteButton(page)).toHaveCount(1)
	await expect(newNoteButton(page)).toBeVisible()
}

async function createCategory(page: Page, name: string): Promise<void> {
	const navigation = appNavigation(page)

	await newCategoryButton(page).click()

	const input = navigation.getByPlaceholder('New category', { exact: true })
	await expect(input).toBeVisible()
	await input.fill(name)
	await input.press('Enter')

	await expect(navigationRow(page, name)).toBeVisible()
	await expectNavigationItemActive(page, name)
}

async function ensureNotesView(page: Page): Promise<void> {
	if (await notesSearchField(page).isVisible()) {
		return
	}

	const previousNoteId = currentNoteId(page)
	await newNoteButton(page).click()
	await waitForNoteRoute(page, previousNoteId)
	await expect(notesSearchField(page)).toBeVisible()
}

async function createNoteInSelectedCategory(page: Page, category: string): Promise<number> {
	await ensureNotesView(page)

	const previousNoteId = currentNoteId(page)
	await notesViewNewNoteButton(page).click()

	const noteId = await waitForNoteRoute(page, previousNoteId)
	await expect(navigationRow(page, category).locator('.app-navigation-entry__counter-wrapper')).toContainText('1')

	return noteId
}

async function openCategoryActions(page: Page, category: string): Promise<void> {
	const row = navigationRow(page, category)

	await expect(row).toBeVisible()
	await row.hover()

	const actionsButton = row.getByRole('button', { name: 'Actions', exact: true })
	await expect(actionsButton).toBeVisible()
	await actionsButton.click()
}

async function createNoteViaApi(page: Page, category: string, title: string): Promise<number> {
	const user = process.env.NC_USER ?? 'admin'
	const password = process.env.NC_PASS ?? 'admin'
	const headers = { Authorization: `Basic ${Buffer.from(`${user}:${password}`).toString('base64')}` }

	const response = await page.request.post('/index.php/apps/notes/api/v1/notes', {
		headers,
		data: { category, content: `# ${title}` },
	})
	expect(response.ok(), `creating note in category "${category}"`).toBeTruthy()

	const note = await response.json() as { id: number }
	return note.id
}

async function openNoteFromList(page: Page, noteId: number): Promise<void> {
	const previousNoteId = currentNoteId(page)
	if (previousNoteId === noteId) {
		return
	}

	const noteLink = page.locator(`a[href$="/note/${noteId}"]`).first()
	await expect(noteLink).toBeVisible()
	await noteLink.click()
	await waitForNoteRoute(page, previousNoteId)
}

test.describe('Category actions', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
		await openNotesApp(page)
	})

	test('renames a category from the actions menu', async ({ page }, testInfo: TestInfo) => {
		const category = uniqueTitle('rename', testInfo)
		const renamedCategory = `${category} renamed`
		const noteId = await (async () => {
			await createCategory(page, category)
			return createNoteInSelectedCategory(page, category)
		})()

		await openCategoryActions(page, category)
		await page.getByRole('menuitem', { name: 'Rename category', exact: true }).click()

		const renameInput = appNavigation(page).getByPlaceholder(category, { exact: true })
		await expect(renameInput).toBeVisible()
		await renameInput.fill(renamedCategory)
		await renameInput.press('Enter')

		await expect(navigationRow(page, renamedCategory)).toBeVisible()
		await expectNavigationItemActive(page, renamedCategory)
		await expect(navigationRow(page, renamedCategory).locator('.app-navigation-entry__counter-wrapper')).toContainText('1')
		await expect(navigationRow(page, category)).toHaveCount(0)
		await expect(page).toHaveURL(new RegExp(`/note/${noteId}(\\?.*)?$`))
	})

	test('deletes a category from the actions menu', async ({ page }, testInfo: TestInfo) => {
		const category = uniqueTitle('delete', testInfo)
		await createCategory(page, category)
		const noteId = await createNoteInSelectedCategory(page, category)
		const deletedNoteUrl = new RegExp(`/note/${noteId}(\\?.*)?$`)

		await openCategoryActions(page, category)
		await page.getByRole('menuitem', { name: 'Delete category', exact: true }).click()

		const confirmationText = `Delete category "${category}" and its 1 note?`
		await expect(page.getByText(confirmationText, { exact: true })).toBeVisible()

		await page.getByRole('button', { name: 'Delete', exact: true }).click()

		await expect(navigationRow(page, category)).toHaveCount(0)
		await expectNavigationItemActive(page, 'All notes')
		await expect(page).not.toHaveURL(deletedNoteUrl)
	})

	test('keeps All notes selected when opening a categorized note', async ({ page }, testInfo: TestInfo) => {
		const category = uniqueTitle('all-notes', testInfo)
		const seedNoteId = await createNoteViaApi(page, '', 'uncategorized seed')
		const noteId = await createNoteViaApi(page, category, `${category} note`)

		await openNotesApp(page)
		await expect(navigationRow(page, category)).toBeVisible()

		await navigationLink(page, 'All notes').click()
		await expectNavigationItemActive(page, 'All notes')

		// Start from an uncategorized note, then open the categorized one.
		await openNoteFromList(page, seedNoteId)
		await openNoteFromList(page, noteId)

		// Opening a categorized note must not switch the active category away from All notes.
		await expect(page).toHaveURL(new RegExp(`/note/${noteId}(\\?.*)?$`))
		await expectNavigationItemActive(page, 'All notes')
		await expect(navigationLink(page, category)).not.toHaveAttribute('aria-current', 'page')
	})
})

test.describe('Drag and drop', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
		await page.goto('/index.php/apps/notes/')
		await expect(newCategoryButton(page)).toBeVisible()
	})

	test('moves a note into a category by dragging it', async ({ page }, testInfo: TestInfo) => {
		const category = uniqueTitle('drag-target', testInfo)
		const title = uniqueTitle('drag-note', testInfo)

		await createCategory(page, category)
		const noteId = await createNote(page, title)

		await noteRow(page, noteId).dragTo(navigationRow(page, category))

		await expect(navigationRow(page, category).locator('.app-navigation-entry__counter-wrapper')).toContainText('1')
		await navigationRow(page, category).getByRole('link').click()
		await expect(noteRow(page, noteId)).toBeVisible()
	})
})
