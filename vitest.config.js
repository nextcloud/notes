/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig } from 'vitest/config'

export default defineConfig({
	test: {
		include: ['src/tests/**/*.spec.js'],
		// jsdom rather than node: escapeHtml() builds an element, and the app
		// logger other modules pull in reads window at import time. A Vue plugin
		// is only needed once a component is tested.
		environment: 'jsdom',
		coverage: {
			provider: 'v8',
			reporter: ['text', 'lcovonly'],
			include: ['src/**/*.js'],
		},
	},
})
