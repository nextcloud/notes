/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, type Locator, type Page, type TestInfo, test } from '@playwright/test'
import { login } from '../support/login'

function appNavigation(page: Page): Locator {
	return page.getByRole('navigation').filter({
		has: page.getByRole('button', { name: 'New category', exact: true }),
	}).first()
}

function newCategoryButton(page: Page): Locator {
	return page.getByRole('button', { name: 'New category', exact: true })
}

function contentNewNoteButton(page: Page): Locator {
	return page.getByRole('button', { name: 'New note', exact: true })
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

function uniqueCategoryName(prefix: string, testInfo: TestInfo): string {
	return `Playwright ${prefix} ${testInfo.parallelIndex}-${Date.now()}`
}

function currentNoteId(page: Page): number | null {
	const match = page.url().match(/\/note\/(\d+)(?:\?.*)?$/)
	return match ? Number(match[1]) : null
}

async function openNotesApp(page: Page): Promise<void> {
	await page.goto('/index.php/apps/notes/')
	await expect(newCategoryButton(page)).toBeVisible()
	await expect(contentNewNoteButton(page)).toHaveCount(1)
	await expect(contentNewNoteButton(page)).toBeVisible()
}

async function createCategory(page: Page, name: string): Promise<void> {
	const navigation = appNavigation(page)

	await newCategoryButton(page).click()

	const input = navigation.getByPlaceholder('New category', { exact: true })
	await expect(input).toBeVisible()
	await input.fill(name)
	await input.press('Enter')

	await expect(navigationRow(page, name)).toBeVisible()
	await expect(navigationRow(page, name)).toHaveClass(/active/)
}

async function waitForNewNoteRoute(page: Page, previousNoteId: number | null): Promise<number> {
	await expect.poll(() => currentNoteId(page)).not.toBe(previousNoteId)

	const noteId = currentNoteId(page)
	if (noteId === null || noteId === previousNoteId) {
		throw new Error('Expected a new note route after creating a note')
	}

	return noteId
}

async function createNoteViaApi(page: Page, category: string, title: string): Promise<number> {
	return page.evaluate(async ({ category, title }) => {
		const requestToken = (window as unknown as { OC: { requestToken: string } }).OC.requestToken
		const response = await fetch('/index.php/apps/notes/notes', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				requesttoken: requestToken,
			},
			body: JSON.stringify({ category, title, content: '' }),
		})
		if (!response.ok) {
			throw new Error(`Failed to create note: ${response.status} ${await response.text()}`)
		}

		const note = await response.json() as { id: number }
		return note.id
	}, { category, title })
}

async function ensureNotesView(page: Page): Promise<void> {
	if (await notesSearchField(page).isVisible()) {
		return
	}

	const previousNoteId = currentNoteId(page)
	await contentNewNoteButton(page).click()
	await waitForNewNoteRoute(page, previousNoteId)
	await expect(notesSearchField(page)).toBeVisible()
}

async function createNoteInSelectedCategory(page: Page, category: string): Promise<number> {
	await ensureNotesView(page)

	const previousNoteId = currentNoteId(page)
	await notesViewNewNoteButton(page).click()

	const noteId = await waitForNewNoteRoute(page, previousNoteId)
	await expect(navigationRow(page, category).locator('.app-navigation-entry__counter-wrapper')).toContainText('1')

	return noteId
}

async function openCategoryActions(page: Page, category: string): Promise<void> {
	const row = navigationRow(page, category)

	await expect(row).toBeVisible()
	await row.getByRole('button', { name: 'Actions', exact: true }).click()
}

test.describe('Category actions', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
		await openNotesApp(page)
	})

	test('renames a category from the actions menu', async ({ page }, testInfo: TestInfo) => {
		const category = uniqueCategoryName('rename', testInfo)
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
		await expect(navigationRow(page, renamedCategory)).toHaveClass(/active/)
		await expect(navigationRow(page, renamedCategory).locator('.app-navigation-entry__counter-wrapper')).toContainText('1')
		await expect(navigationRow(page, category)).toHaveCount(0)
		await expect(page).toHaveURL(new RegExp(`/note/${noteId}(\\?.*)?$`))
	})

	test('deletes a category from the actions menu', async ({ page }, testInfo: TestInfo) => {
		const category = uniqueCategoryName('delete', testInfo)
		await createCategory(page, category)
		const noteId = await createNoteInSelectedCategory(page, category)
		const deletedNoteUrl = new RegExp(`/note/${noteId}(\\?.*)?$`)

		await openCategoryActions(page, category)
		await page.getByRole('menuitem', { name: 'Delete category', exact: true }).click()

		const confirmationText = `Delete category "${category}" and its 1 note?`
		await expect(page.getByText(confirmationText, { exact: true })).toBeVisible()

		await page.getByRole('button', { name: 'Delete', exact: true }).click()

		await expect(navigationRow(page, category)).toHaveCount(0)
		await expect(navigationRow(page, 'All notes')).toHaveClass(/active/)
		await expect(page).not.toHaveURL(deletedNoteUrl)
	})

	test('keeps all notes selected when opening a categorized note', async ({ page }, testInfo: TestInfo) => {
		const category = uniqueCategoryName('all-notes', testInfo)
		const title = `${category} note`
		const noteId = await createNoteViaApi(page, category, title)

		await page.goto('/index.php/apps/notes/')
		await expect(contentNewNoteButton(page)).toBeVisible()
		await expect(navigationRow(page, category)).toBeVisible()

		await navigationRow(page, 'All notes').click()
		await expect(navigationRow(page, 'All notes')).toHaveClass(/active/)

		const noteLink = page.locator(`a[href$="/note/${noteId}"]`).first()
		await expect(noteLink).toBeVisible()
		await noteLink.click()

		await expect(page).toHaveURL(new RegExp(`/note/${noteId}(\\?.*)?$`))
		await expect(navigationRow(page, 'All notes')).toHaveClass(/active/)
		await expect(navigationRow(page, category)).not.toHaveClass(/active/)
	})
})
