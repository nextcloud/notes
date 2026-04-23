/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getClient, getDefaultPropfind, getRootPath, registerDavProperty, resultToNode } from '@nextcloud/files/dav'

const client = getClient()

let sharingDavPropertiesRegistered = false

function normalizePath(path = '/') {
	if (!path || path === '/') {
		return '/'
	}

	return '/' + path.toString().replace(/^\/+/, '').replace(/\/+$/, '')
}

function registerSharingDavProperties() {
	if (sharingDavPropertiesRegistered) {
		return
	}

	registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })
	registerDavProperty('oc:share-types', { oc: 'http://owncloud.org/ns' })
	registerDavProperty('ocs:share-permissions', { ocs: 'http://open-collaboration-services.org/ns' })
	sharingDavPropertiesRegistered = true
}

export const fetchDavNode = async (path) => {
	registerSharingDavProperties()

	const result = await client.stat(`${getRootPath()}${normalizePath(path)}`, {
		details: true,
		data: getDefaultPropfind(),
	})

	return resultToNode(result.data)
}
