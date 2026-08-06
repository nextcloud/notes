/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page, TestInfo } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { login } from '../support/login.ts'
import { currentNoteId, newNoteButton, uniqueTitle, waitForNoteRoute } from '../support/note.ts'
import { NoteEditor } from '../support/sections/NoteEditor.ts'
import { removeTemplates, stubNoTemplates, stubTemplate } from '../support/templates.ts'

function templatePicker(page: Page): Locator {
	return page.getByRole('dialog').filter({ has: page.locator('.template-picker__list') })
}

async function openNotes(page: Page): Promise<void> {
	await page.goto('/index.php/apps/notes/')
	await expect(newNoteButton(page)).toBeVisible()
}

function templateContent(testInfo: TestInfo): string {
	return `# ${uniqueTitle('template', testInfo)}\n\n- [ ] Prepare the agenda`
}

/**
 * Waiting for the editor keeps the assertion on its value rather than on the
 * rendered text, which drops the line breaks.
 */
async function expectNoteContent(page: Page, content: string): Promise<void> {
	const editor = new NoteEditor(page)
	await expect(editor.codeMirror).toBeVisible()
	await editor.expectText(content)
}

test.describe('Note templates', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
	})

	test.afterEach(async ({ request }) => {
		await removeTemplates(request)
	})

	test('creates a note right away when there are no templates', async ({ page }) => {
		await stubNoTemplates(page)
		await openNotes(page)

		const previousNoteId = currentNoteId(page)
		await newNoteButton(page).click()
		await waitForNoteRoute(page, previousNoteId)

		await expect(templatePicker(page)).toHaveCount(0)
	})

	test('creates a note from a template', async ({ page, request }, testInfo: TestInfo) => {
		const content = templateContent(testInfo)
		const label = await stubTemplate(page, request, content)
		await openNotes(page)

		const previousNoteId = currentNoteId(page)
		await newNoteButton(page).click()

		const picker = templatePicker(page)
		await expect(picker).toBeVisible()
		await expect(picker.getByRole('radio', { name: 'Blank note' })).toBeChecked()

		await picker.getByText(label, { exact: true }).click()
		await expect(picker.getByRole('radio', { name: label })).toBeChecked()
		await picker.getByRole('button', { name: 'Create note' }).click()

		await waitForNoteRoute(page, previousNoteId)
		await expect(picker).toHaveCount(0)
		await expectNoteContent(page, content)
	})

	test('creates a blank note from the picker', async ({ page, request }, testInfo: TestInfo) => {
		await stubTemplate(page, request, templateContent(testInfo))
		await openNotes(page)

		const previousNoteId = currentNoteId(page)
		await newNoteButton(page).click()

		const picker = templatePicker(page)
		await expect(picker).toBeVisible()
		await picker.getByRole('button', { name: 'Create note' }).click()

		await waitForNoteRoute(page, previousNoteId)
		await expectNoteContent(page, '')
	})

	test('keeps the note untouched when the picker is cancelled', async ({ page, request }, testInfo: TestInfo) => {
		await stubTemplate(page, request, templateContent(testInfo))
		await openNotes(page)

		const previousNoteId = currentNoteId(page)
		await newNoteButton(page).click()

		const picker = templatePicker(page)
		await expect(picker).toBeVisible()
		await picker.getByRole('button', { name: 'Cancel' }).click()

		await expect(picker).toHaveCount(0)
		expect(currentNoteId(page)).toBe(previousNoteId)
	})
})
