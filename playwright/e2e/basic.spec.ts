/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '@playwright/test'
import { login } from '../support/login'
import { NoteEditor } from '../support/sections/NoteEditor'

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
		const newNoteButton = page.getByRole('button', { name: 'New note', exact: true })
		await expect(newNoteButton).toBeVisible()
		await newNoteButton.click()

		const editor = new NoteEditor(page)
		await editor.type('Hello from Playwright')
		await editor.expectText('Hello from Playwright')
	})
})
