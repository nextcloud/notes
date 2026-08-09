/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page, TestInfo } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { login } from '../support/login.ts'
import { createNote, deleteAllNotes, newNoteButton, uniqueTitle } from '../support/note.ts'
import { NoteEditor } from '../support/sections/NoteEditor.ts'

function navigation(page: Page): Locator {
	return page.locator('.app-navigation')
}

function noteList(page: Page): Locator {
	return page.locator('.splitpanes__pane-list')
}

function zenModeEntry(page: Page): Locator {
	return page.getByRole('link', { name: 'Zen mode', exact: true })
}

function exitZenModeButton(page: Page): Locator {
	return page.getByRole('button', { name: 'Exit zen mode', exact: true })
}

async function expectZenMode(page: Page, active: boolean): Promise<void> {
	if (active) {
		await expect(exitZenModeButton(page)).toBeVisible()
		await expect(navigation(page)).toBeHidden()
		await expect(noteList(page)).toBeHidden()
	} else {
		await expect(exitZenModeButton(page)).toHaveCount(0)
		await expect(navigation(page)).toBeVisible()
		await expect(noteList(page)).toBeVisible()
	}
}

async function enterZenMode(page: Page): Promise<void> {
	await zenModeEntry(page).click()
	await expectZenMode(page, true)
}

test.describe('Zen mode', () => {
	test.beforeEach(async ({ page }) => {
		await login(page)
		await page.goto('/index.php/apps/notes/')
		await expect(newNoteButton(page)).toBeVisible()
	})

	test('hides the navigation and the note list', async ({ page }, testInfo: TestInfo) => {
		await createNote(page, uniqueTitle('zen', testInfo))

		await expectZenMode(page, false)
		await enterZenMode(page)
	})

	test('leaves zen mode with the exit button', async ({ page }, testInfo: TestInfo) => {
		await createNote(page, uniqueTitle('zen exit button', testInfo))

		await enterZenMode(page)
		await exitZenModeButton(page).click()
		await expectZenMode(page, false)
	})

	test('toggles zen mode with the keyboard shortcut', async ({ page }, testInfo: TestInfo) => {
		await createNote(page, uniqueTitle('zen shortcut', testInfo))

		await page.keyboard.press('Control+Period')
		await expectZenMode(page, true)

		await page.keyboard.press('Control+Period')
		await expectZenMode(page, false)
	})

	test('ignores key repeat while the shortcut is held', async ({ page }, testInfo: TestInfo) => {
		await createNote(page, uniqueTitle('zen repeat', testInfo))

		// Playwright marks every repeated keydown of a held key as `repeat`. An even
		// number of them would cancel itself out if they were not ignored.
		await page.keyboard.down('Control')
		await page.keyboard.down('Period')
		await page.keyboard.down('Period')
		await page.keyboard.up('Period')
		await page.keyboard.up('Control')

		await expectZenMode(page, true)
	})

	test('announces the shortcut of the current platform', async ({ page }, testInfo: TestInfo) => {
		await createNote(page, uniqueTitle('zen shortcut hint', testInfo))

		const isAppleDevice = await page.evaluate(() => {
			const platform = (navigator as Navigator & { userAgentData?: { platform?: string } })
				.userAgentData?.platform ?? navigator.platform ?? ''
			return /mac|iphone|ipad|ipod/i.test(platform)
		})

		await enterZenMode(page)
		await expect(exitZenModeButton(page)).toHaveAttribute('title', isAppleDevice ? 'Cmd + .' : 'Ctrl + .')
	})

	test('leaves zen mode with Escape but never enters it', async ({ page }, testInfo: TestInfo) => {
		await createNote(page, uniqueTitle('zen escape', testInfo))

		await page.keyboard.press('Escape')
		await expectZenMode(page, false)

		await enterZenMode(page)
		await page.keyboard.press('Escape')
		await expectZenMode(page, false)
	})

	test('ignores the shortcut while a dialog is open', async ({ page }, testInfo: TestInfo) => {
		await createNote(page, uniqueTitle('zen dialog', testInfo))

		await page.getByRole('link', { name: 'Notes settings', exact: true }).click()
		const dialog = page.locator('.dialog__modal')
		await expect(dialog).toBeVisible()

		await page.keyboard.press('Control+Period')
		await expect(exitZenModeButton(page)).toHaveCount(0)
		await expect(dialog).toBeVisible()
	})

	test('keeps the note editable in zen mode', async ({ page }, testInfo: TestInfo) => {
		const title = uniqueTitle('zen editing', testInfo)
		await createNote(page, title)

		await enterZenMode(page)

		const editor = new NoteEditor(page)
		const content = `${title}\n\nWritten without the chrome`
		await editor.type(content)
		await editor.expectText(content)
	})

	test('is not offered while no note is open', async ({ page }) => {
		await deleteAllNotes(page)
		await page.goto('/index.php/apps/notes/')
		await expect(page).toHaveURL(/\/welcome$/)

		await expect(zenModeEntry(page)).toHaveCount(0)

		await page.keyboard.press('Control+Period')
		await expect(exitZenModeButton(page)).toHaveCount(0)
		await expect(navigation(page)).toBeVisible()
	})

	test('does not survive a reload', async ({ page }, testInfo: TestInfo) => {
		await createNote(page, uniqueTitle('zen reload', testInfo))

		await enterZenMode(page)

		await page.reload()
		await expect(newNoteButton(page)).toBeVisible()
		await expectZenMode(page, false)
	})
})
