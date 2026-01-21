/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from "@playwright/test";

export class NoteEditor {
	public readonly el: Locator;
	public readonly content: Locator;

	constructor(public readonly page: Page) {
		this.el = this.page.locator(".editor").first();
		this.content = this.el.getByRole("textbox");
	}

	public async type(keys: string): Promise<void> {
		await this.content.pressSequentially(keys);
	}
}
