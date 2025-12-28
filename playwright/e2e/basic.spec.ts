import { expect, test } from '@playwright/test'
import { login } from '../support/login'

test.describe('Basic checks', () => {
    test.beforeEach(async ({ page }) => {
        await login(page)
    })

	test('Notes app is visible', async ({ page }) => {
		await page.goto('/index.php/apps/notes/')
		await expect(page).toHaveTitle(/Notes/)
	})
})
