/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '@playwright/test'
import { login } from '../support/login'
import { NoteEditor } from '../support/sections/NoteEditor'

// eslint-disable-next-line @typescript-eslint/no-var-requires, @typescript-eslint/no-require-imports
const pdf = require('pdf-parse')

test.describe('Print to PDF', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
	})

	async function createNote(page, title: string, content: string) {
		await page.goto('/index.php/apps/notes/')
		await page
			.locator('#app-navigation-vue')
			.getByRole('button', { name: 'New note' })
			.click()
		const editor = new NoteEditor(page)
		await editor.type(content)
		// Wait a bit for note to save
		await page.waitForTimeout(1000)
	}

	test('Print CSS hides non-essential UI elements', async ({ page }) => {
		const noteTitle = `Test Note ${Date.now()}`
		const noteContent = `${noteTitle}\nThis is the note content.`
		await createNote(page, noteTitle, noteContent)

		// Check that header and sidebar are visible initially
		await expect(page.locator('#header')).toBeVisible()
		await expect(page.locator('.app-navigation')).toBeVisible()

		// Switch to print media
		await page.emulateMedia({ media: 'print' })

		// Assert that print CSS hides elements
		await expect(page.locator('#header')).toBeHidden()
		await expect(page.locator('.app-navigation')).toBeHidden()
		await expect(page.locator('.action-buttons')).toBeHidden()
		await expect(page.locator('.upload-button')).toBeHidden()

		// Switch back to screen (optional)
		await page.emulateMedia({ media: 'screen' })
	})

	test('Print note to PDF contains note title and non-empty', async ({ page }) => {
		const browserName = test.info().project.name

		// PDF generation only works in Chromium
		if (browserName !== 'chromium') {
			test.skip()
			return
		}

		const noteTitle = `Test Note ${Date.now()}`
		const noteContent = `${noteTitle}\nThis is the note content.`
		await createNote(page, noteTitle, noteContent)

		// Generate PDF
		const pdfBuffer = await page.pdf({
			// Ensure print styles are applied
			preferCSSPageSize: true,
			printBackground: true,
			// Optionally set margins
			margin: {
				top: '0.5in',
				bottom: '0.5in',
				left: '0.5in',
				right: '0.5in',
			},
		})

		// Assert PDF is non-empty
		expect(pdfBuffer.length).toBeGreaterThan(0)

		// Parse PDF text
		const pdfData = await pdf(pdfBuffer)
		const pdfText = pdfData.text

		// Assert PDF contains note title
		expect(pdfText).toContain(noteTitle)
	})
})
