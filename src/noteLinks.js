/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'

/**
 * Notes are linked with ordinary markdown links pointing at the note's route:
 *
 *     [Shopping list](/index.php/apps/notes/note/42)
 *
 * No custom syntax, so every editor renders them — the rich editor, the
 * markdown preview, and anything else that reads the file. The target is the
 * file id rather than the title, so renaming a note cannot break a link to it.
 *
 * This mirrors the URL NoteReferenceProvider already matches on the server, so
 * a link pasted into Talk or a Text document still resolves to a rich preview.
 */

/**
 * @param {number|string} noteId id of the note to link to
 * @return {string} URL for a markdown link
 */
export function noteLinkUrl(noteId) {
	return generateUrl('apps/notes/note/{noteId}', { noteId })
}

/**
 * A markdown link to a note, ready to paste into another note.
 *
 * @param {object} note the note to link to
 * @return {string} markdown
 */
export function noteLinkMarkdown(note) {
	// ']' would end the label early, so it is the one character worth escaping
	const label = String(note?.title ?? '').replaceAll(']', '\\]')

	return `[${label}](${noteLinkUrl(note.id)})`
}

/**
 * The note a link points at, or null when it points somewhere else.
 *
 * Accepts every shape the link may have been written in — absolute or
 * root-relative, with or without the `/index.php` prefix, and under a
 * subdirectory install — by resolving it and matching on the path. Links to
 * another origin are never treated as note links, so a URL that merely looks
 * like one cannot make Notes navigate.
 *
 * @param {string} href the link target
 * @return {number|null} note id, or null
 */
export function parseNoteLink(href) {
	if (!href) {
		return null
	}

	let url
	try {
		url = new URL(href, window.location.href)
	} catch {
		return null
	}

	if (url.origin !== window.location.origin) {
		return null
	}

	const match = url.pathname.match(/\/apps\/notes\/note\/(\d+)\/*$/)

	return match ? Number(match[1]) : null
}
