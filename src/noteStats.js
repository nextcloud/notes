/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** Words per minute used for the reading estimate — the usual figure for prose. */
const WORDS_PER_MINUTE = 200

/**
 * Strips the markup so a word count counts words rather than syntax.
 *
 * Deliberately shallow: it drops the things that would otherwise be counted as
 * words — fenced code, link targets, image syntax, heading and list markers —
 * and leaves everything else alone. A full parse would be more accurate and far
 * more code for a number nobody checks to the decimal.
 *
 * @param {string} content raw markdown
 * @return {string} text with the obvious markup removed
 */
function stripMarkup(content) {
	return content
		// fenced code blocks, including the fence lines
		.replaceAll(/^```[\s\S]*?^```/gm, ' ')
		.replaceAll(/^~~~[\s\S]*?^~~~/gm, ' ')
		// images: drop entirely, they contribute no words
		.replaceAll(/!\[[^\]]*\]\([^)]*\)/g, ' ')
		// links: keep the label, drop the target
		.replaceAll(/\[([^\]]*)\]\([^)]*\)/g, '$1')
		// inline code, keeping what is inside it
		.replaceAll(/`([^`]*)`/g, '$1')
		// heading, blockquote and list markers at the start of a line
		.replaceAll(/^\s{0,3}#{1,6}\s+/gm, '')
		.replaceAll(/^\s*>+\s?/gm, '')
		// \u00A0 escaped rather than literal: markdown-it-task-checkbox accepts a
		// non-breaking space inside the brackets, and a bare one here is invisible
		.replaceAll(/^\s*(?:[-+*]|\d+\.)\s+(?:\[[xX \u00A0]\]\s+)?/gm, '')
		// setext underlines and thematic breaks
		.replaceAll(/^\s*(?:={2,}|-{3,}|\*{3,}|_{3,})\s*$/gm, ' ')
		// emphasis markers
		.replaceAll(/(\*{1,3}|_{1,3}|~~)(?=\S)([\s\S]*?\S)\1/g, '$2')
}

/**
 * Counts for a note's body.
 *
 * @param {string} content raw markdown, may be empty or absent
 * @return {{words: number, readingMinutes: number}} counts
 */
export function noteTextStats(content) {
	const text = typeof content === 'string' ? content : ''
	const stripped = stripMarkup(text).trim()
	const words = stripped === '' ? 0 : stripped.split(/\s+/u).length

	return {
		words,
		// never round an existing note down to "0 min"
		readingMinutes: words === 0 ? 0 : Math.max(1, Math.round(words / WORDS_PER_MINUTE)),
	}
}
