/**
 * SPDX-FileCopyrightText: 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../support/fixtures/random-user'

test.beforeEach(async ({ page }) => {
	await page.goto('apps/notes')
	await page.waitForURL(/apps\/notes/)
})

test('Random user is authenticated', async ({ page, user }) => {
	await expect(page.getByLabel('Settings menu')).toBeVisible()
	expect(user.userId).toEqual(user.password)
})

test('Can see the icon', async ({ page }) => {
	await expect(page.getByRole('link', { name: 'Notes Notes' })).toBeVisible()
})
