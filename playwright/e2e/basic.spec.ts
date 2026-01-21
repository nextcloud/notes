/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from "@playwright/test";
import { login } from "../support/login";
import { NoteEditor } from "../support/sections/NoteEditor";

test.describe("Basic checks", () => {
	test.beforeEach(async ({ page }) => {
		await login(page);
	});

	test("Notes app is visible", async ({ page }) => {
		await page.goto("/index.php/apps/notes/");
		await expect(page).toHaveTitle(/Notes/);
	});

	test("Create note and type", async ({ page }) => {
		await page.goto("/index.php/apps/notes/");
		await page
			.locator("#app-navigation-vue")
			.getByRole("button", { name: "New note" })
			.click();
		const editor = new NoteEditor(page);
		await editor.type("Hello from Playwright");
		await expect(editor.content).toContainText("Hello from Playwright");
	});
});
