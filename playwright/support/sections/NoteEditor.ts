/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, type Locator, type Page } from '@playwright/test'

export class NoteEditor {

	public readonly el: Locator
	public readonly codeMirror: Locator
	public readonly surface: Locator
	public readonly textarea: Locator

	constructor(public readonly page: Page) {
		this.el = this.page.locator('.text-editor, .note-editor').first()
		this.codeMirror = this.el.locator('.CodeMirror').first()
		this.surface = this.el
			.locator('.ProseMirror, .CodeMirror-code[contenteditable="true"], [contenteditable="true"]')
			.first()
		this.textarea = this.el.locator('textarea').first()
	}

	public async type(keys: string): Promise<void> {
		await expect(this.el).toBeVisible()

		if (await this.codeMirror.count() > 0) {
			await expect(this.codeMirror).toBeVisible()
			await this.codeMirror.evaluate((element, value) => {
				const cm = (element as HTMLElement & {
					CodeMirror?: {
						focus: () => void
						setValue: (text: string) => void
					}
				}).CodeMirror
				cm?.focus()
				cm?.setValue(value)
			}, keys)
			return
		}

		await expect(this.surface).toBeVisible()
		await this.surface.click({ position: { x: 8, y: 8 } })
		await this.page.keyboard.insertText(keys)
	}

	public async expectText(keys: string): Promise<void> {
		if (await this.codeMirror.count() > 0) {
			await expect.poll(async () => {
				return await this.codeMirror.evaluate((element) => {
					const cm = (element as HTMLElement & {
						CodeMirror?: {
							getValue: () => string
						}
					}).CodeMirror
					return cm?.getValue() ?? ''
				})
			}).toBe(keys)
			return
		}

		if (await this.textarea.count() > 0) {
			await expect(this.textarea).toHaveValue(keys)
			return
		}

		await expect(this.surface).toContainText(keys)
	}

}
