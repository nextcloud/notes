/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { getClient, getRootPath } from '@nextcloud/files/dav'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import logger from './Logger.js'

/** Mimetypes a note can be made from; the Office formats are dropped. */
const NOTE_MIMETYPES = ['text/markdown', 'text/plain']

/** Requested size of a template card's thumbnail, in pixels. */
const PREVIEW_SIZE = 256

/**
 * URL of a template's thumbnail, or null when there is none to show.
 *
 * The endpoint reports `hasPreview` but leaves `previewUrl` at null for the
 * user's own templates, so the URL has to be built from the file id.
 *
 * @param {object} template a template as reported by the endpoint
 * @return {string|null} preview URL, or null
 */
function previewUrl(template) {
	if (template.previewUrl) {
		return template.previewUrl
	}
	if (!template.hasPreview || !template.fileid) {
		return null
	}

	return generateUrl('/core/preview?fileId={fileId}&x={x}&y={y}&a={a}', {
		fileId: template.fileid,
		x: PREVIEW_SIZE,
		y: PREVIEW_SIZE,
		// keep the aspect ratio rather than cropping the note
		a: 1,
	})
}

/**
 * Templates the user can start a note from, taken from the Files app's
 * template endpoint. It answers with one entry per registered template creator
 * (Text, Office, …), each carrying its templates, which are flattened here.
 *
 * Never throws: failing to list templates must not stop note creation.
 *
 * @return {Promise<Array<object>>} templates, or an empty list
 */
export async function fetchNoteTemplates() {
	try {
		const response = await axios.get(generateOcsUrl('apps/files/api/v1/templates'))
		const creators = response.data?.ocs?.data ?? []

		return creators
			.flatMap((creator) => (creator.templates ?? [])
				.filter((template) => NOTE_MIMETYPES.includes(template.mime))
				.map((template) => ({
					...template,
					previewUrl: previewUrl(template),
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
 * `templateId` is the template's path relative to the user's files root, so it
 * is fetched over WebDAV.
 *
 * @param {string} templateId path of the template, relative to the user root
 * @return {Promise<string>} the template's text
 * @throws {Error} when the path tries to leave the user's files root
 */
export async function fetchTemplateContent(templateId) {
	const path = '/' + String(templateId).replace(/^\/+/, '')
	if (path.split('/').includes('..')) {
		throw new Error('Template path must stay inside the user root')
	}

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
