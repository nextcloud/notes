/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { getClient, getRootPath } from '@nextcloud/files/dav'
import { generateOcsUrl } from '@nextcloud/router'
import logger from './Logger.js'

/**
 * Templates a note can be made from. Anything else the server offers — the
 * Office formats in particular — is a binary file that would be meaningless as
 * a note.
 */
const NOTE_MIMETYPES = ['text/markdown', 'text/plain']

/**
 * Templates the user can start a note from.
 *
 * These come from the Files app's template endpoint, which is the same source
 * the Files "New" menu reads, so whatever lives in the user's configured
 * Templates folder shows up here too. The endpoint answers with one entry per
 * registered template *creator* (Text, Office, …), each carrying the templates
 * that match its mimetypes; we keep the text ones and flatten them.
 *
 * Never throws: templates are a convenience, and failing to list them must not
 * stop somebody from creating a note.
 *
 * @return {Promise<Array<object>>} templates, or an empty list
 */
export async function fetchNoteTemplates() {
	try {
		const response = await axios.get(generateOcsUrl('apps/files/api/v1/templates'))
		const creators = response.data?.ocs?.data ?? []

		return creators
			.filter((creator) => (creator.mimetypes ?? []).some((mime) => NOTE_MIMETYPES.includes(mime)))
			.flatMap((creator) => (creator.templates ?? []).map((template) => ({
				...template,
				// the creator's icon is the sensible stand-in when a template has
				// no preview of its own
				iconSvgInline: creator.iconSvgInline,
			})))
	} catch (error) {
		logger.warn('Listing note templates has failed', { error })
		return []
	}
}

/**
 * Read a template's content so it can be used as the body of a new note.
 *
 * `templateId` is the template's path relative to the user's files root, which
 * is what the endpoint above reports, so it can be fetched over WebDAV
 * directly rather than through a Notes endpoint.
 *
 * @param {string} templateId path of the template, relative to the user root
 * @return {Promise<string>} the template's text
 */
export async function fetchTemplateContent(templateId) {
	const path = '/' + String(templateId).replace(/^\/+/, '')
	const content = await getClient().getFileContents(`${getRootPath()}${path}`, { format: 'text' })

	return typeof content === 'string' ? content : ''
}

/**
 * Strip the extension so "Meeting notes.md" reads as "Meeting notes".
 *
 * @param {object} template a template as returned by fetchNoteTemplates()
 * @return {string} label to show in the picker
 */
export function templateLabel(template) {
	const basename = template.basename ?? ''

	return basename.replace(/\.[^.]+$/, '') || basename
}
