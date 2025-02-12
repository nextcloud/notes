/**
 * SPDX-FileCopyrightText: 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { startNextcloud, stopNextcloud } from '@nextcloud/e2e-test-server/docker'
import { readFileSync } from 'fs'

const start = async () => {
	return await startNextcloud(getBranch(), true, {
		exposePort: 8089,
	})
}

const getBranch = () => {
	try {
		const appinfo = readFileSync('appinfo/info.xml').toString()
		const maxVersion = appinfo.match(
			/<nextcloud min-version="\d+" max-version="(\d\d+)" \/>/,
		)?.[1]
		return maxVersion ? `stable${maxVersion}` : undefined
	} catch (err) {
		if (err.code === 'ENOENT') {
			console.warn('No appinfo/info.xml found. Using default server banch.')
		}
	}
}

// Start the Nextcloud docker container
await start()
// Listen for process to exit (tests done) and shut down the docker container
process.on('beforeExit', (code) => {
	stopNextcloud()
})

// Idle to wait for shutdown
while (true) {
	await new Promise((resolve) => setTimeout(resolve, 5000))
}
