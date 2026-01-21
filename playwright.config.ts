/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig, devices } from "@playwright/test";

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
	testDir: "./playwright",

	/* Run tests in files in parallel */
	fullyParallel: true,
	/* Fail the build on CI if you accidentally left test.only in the source code. */
	forbidOnly: !!process.env.CI,
	/* Retry on CI only */
	retries: process.env.CI ? 2 : 0,
	/* Opt out of parallel tests on CI. */
	workers: process.env.CI ? 1 : undefined,
	/* Reporter to use. See https://playwright.dev/docs/test-reporters */
	reporter: process.env.CI ? "github" : "list",
	/* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
	use: {
		/* Base URL to use in actions like `await page.goto('./')`. */
		baseURL: process.env.BASE_URL ?? "http://localhost:8089",

		/* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
		trace: "on-first-retry",

		/* Capture screenshot on failure */
		screenshot: "only-on-failure",
	},

	webServer: {
		// Starts the Nextcloud docker container
		command: "npm run start:nextcloud",
		// we use sigterm to notify the script to stop the container
		// if it does not respond, we force kill it after 10 seconds
		gracefulShutdown: {
			signal: "SIGTERM",
			timeout: 10000,
		},
		reuseExistingServer: !process.env.CI,
		stderr: "pipe",
		stdout: "pipe",
		url: "http://127.0.0.1:8089",
		timeout: 5 * 60 * 1000, // max. 5 minutes for creating the container
		wait: {
			// we wait for this line to appear in the output of the webserver until consider it done
			stdout: /Nextcloud is now ready to use/,
		},
	},

	projects: [
		{
			name: "chromium",
			use: {
				...devices["Desktop Chrome"],
			},
		},
	],
});
