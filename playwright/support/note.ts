/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page, TestInfo } from '@playwright/test'

import { expect } from '@playwright/test'
import { NoteEditor } from './sections/NoteEditor.ts'

export function uniqueTitle(prefix: string, testInfo: TestInfo): string {
	return `Playwright ${prefix} ${testInfo.parallelIndex}-${Date.now()}`
}

export function currentNoteId(page: Page): number | null {
	const match = page.url().match(/\/note\/(\d+)(?:\?.*)?$/)
	return match ? Number(match[1]) : null
}

export function newNoteButton(page: Page): Locator {
	return page.getByRole('button', { name: 'New note', exact: true })
}

export function noteRow(page: Page, noteId: number): Locator {
	return page.locator(`a[href$="/note/${noteId}"]`).first()
		.locator('xpath=ancestor::li[1]')
}

export async function waitForNoteRoute(page: Page, previousNoteId: number | null): Promise<number> {
	await expect.poll(() => currentNoteId(page)).not.toBe(previousNoteId)

	const noteId = currentNoteId(page)
	if (noteId === null || noteId === previousNoteId) {
		throw new Error('Expected to navigate to a note route')
	}

	return noteId
}

export async function createNote(page: Page, title: string): Promise<number> {
	const previousNoteId = currentNoteId(page)
	await newNoteButton(page).click()
	const noteId = await waitForNoteRoute(page, previousNoteId)

	const editor = new NoteEditor(page)
	await editor.type(title)
	await editor.expectText(title)

	return noteId
}
