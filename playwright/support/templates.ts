/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect } from '@playwright/test'

const TEMPLATES_URL = '**/apps/files/api/v1/templates'

/** Folder the uploaded templates live in, relative to the user root. */
const TEMPLATE_FOLDER = 'Playwright templates'

function user(): string {
	return process.env.NC_USER ?? 'admin'
}

function authHeaders(): Record<string, string> {
	const password = process.env.NC_PASS ?? 'admin'

	return { Authorization: `Basic ${Buffer.from(`${user()}:${password}`).toString('base64')}` }
}

function davUrl(path: string): string {
	return `/remote.php/dav/files/${user()}/${path.split('/').map(encodeURIComponent).join('/')}`
}

function ocsResponse(creators: unknown[]): object {
	return { ocs: { meta: { status: 'ok', statuscode: 200, message: 'OK' }, data: creators } }
}

/**
 * Answer the templates endpoint with an empty list.
 *
 * @param page The page object to use
 */
export async function stubNoTemplates(page: Page): Promise<void> {
	await page.route(TEMPLATES_URL, (route) => route.fulfill({ json: ocsResponse([]) }))
}

/**
 * Upload a markdown template and make the templates endpoint report it.
 *
 * Only the listing is stubbed; the file itself is really there, so the app
 * reads it over WebDAV as it would in production. The stub is needed because
 * nothing registers a template creator for text/markdown on a plain server —
 * that comes from the Text app, which the e2e container does not ship.
 *
 * @param page The page object to use
 * @param request The request context to upload with, cookie-free so that the
 *   writing DAV calls are not rejected by the CSRF check
 * @param content Content of the template file
 * @param options Shape of the template as the endpoint lists it
 * @param options.hasPreview Whether the endpoint claims a preview for it
 * @return The label the picker shows for the template
 */
export async function stubTemplate(
	page: Page,
	request: APIRequestContext,
	content: string,
	{ hasPreview = false }: { hasPreview?: boolean } = {},
): Promise<string> {
	const label = `Playwright template ${Date.now()}`
	const basename = `${label}.md`

	const folder = await request.fetch(davUrl(TEMPLATE_FOLDER), { method: 'MKCOL', headers: authHeaders() })
	expect([201, 405], 'creating the template folder').toContain(folder.status())

	const upload = await request.put(davUrl(`${TEMPLATE_FOLDER}/${basename}`), {
		headers: authHeaders(),
		data: content,
	})
	expect(upload.ok(), `uploading ${basename}`).toBeTruthy()

	// the header reads like "00000031ocqwc6b1e0hh", the file id is the leading number
	const fileid = Number.parseInt(upload.headers()['oc-fileid'] ?? '', 10)
	expect(fileid, 'file id of the uploaded template').not.toBeNaN()

	await page.route(TEMPLATES_URL, (route) => route.fulfill({
		json: ocsResponse([{
			app: 'text',
			label: 'Markdown',
			extension: '.md',
			iconSvgInline: null,
			mimetypes: ['text/markdown'],
			ratio: 1,
			templates: [{
				templateType: 'user',
				templateId: `${TEMPLATE_FOLDER}/${basename}`,
				basename,
				fileid,
				mime: 'text/markdown',
				hasPreview,
				// the endpoint leaves this null for the user's own templates
				previewUrl: null,
			}],
		}]),
	}))

	return label
}

/**
 * @param request The request context to delete with, see stubTemplate()
 */
export async function removeTemplates(request: APIRequestContext): Promise<void> {
	const deletion = await request.delete(davUrl(TEMPLATE_FOLDER), { headers: authHeaders() })
	expect([204, 404], 'removing the template folder').toContain(deletion.status())
}
