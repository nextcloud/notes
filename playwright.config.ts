/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig, devices } from '@playwright/test'

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
	testDir: './playwright',

	/* Run tests in files in parallel */
	fullyParallel: true,
	/* Fail the build on CI if you accidentally left test.only in the source code. */
	forbidOnly: !!process.env.CI,
	/* Retry on CI only */
	retries: process.env.CI ? 2 : 0,
	/* Opt out of parallel tests on CI. */
	workers: process.env.CI ? 1 : undefined,
	/* Reporter to use. See https://playwright.dev/docs/test-reporters */
	reporter: process.env.CI ? 'github' : 'html',
	/* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
	use: {
		/* Base URL to use in actions like `await page.goto('./')`. */
		baseURL: 'http://localhost:8089/index.php/',

		/* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
		trace: 'on-first-retry',
	},

	projects: [
		// Our global setup to configure the Nextcloud docker container
		{
			name: 'setup',
			testMatch: /setup\.ts$/,
		},

		{
			name: 'chromium',
			use: {
				...devices['Desktop Chrome'],
			},
			dependencies: ['setup'],
		},
	],

	webServer: {
		// Starts the Nextcloud docker container
		command: 'npm run start:nextcloud',
		reuseExistingServer: !process.env.CI,
		url: 'http://127.0.0.1:8089',
		stderr: 'pipe',
		stdout: 'pipe',
		timeout: 5 * 60 * 1000, // max. 5 minutes for creating the container
	},
})
