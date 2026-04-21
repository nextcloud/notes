/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, type Locator, type Page } from '@playwright/test'

export class NoteEditor {

	public readonly el: Locator
	public readonly surface: Locator
	public readonly textarea: Locator

	constructor(public readonly page: Page) {
		this.el = this.page.locator('.text-editor, .note-editor').first()
		this.surface = this.el
			.locator('.ProseMirror, .CodeMirror-code[contenteditable="true"], [contenteditable="true"]')
			.first()
		this.textarea = this.el.locator('textarea').first()
	}

	public async type(keys: string): Promise<void> {
		await expect(this.el).toBeVisible()
		await expect(this.surface).toBeVisible()
		await this.surface.click({
			position: { x: 8, y: 8 },
		})
		await this.page.keyboard.insertText(keys)
	}

	public async expectText(keys: string): Promise<void> {
		if (await this.textarea.count() > 0) {
			await expect(this.textarea).toHaveValue(keys)
			return
		}

		await expect(this.surface).toContainText(keys)
	}

}
