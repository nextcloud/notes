/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { noteTextStats } from '../noteStats.js'

const words = (content) => noteTextStats(content).words

describe('noteTextStats words', () => {
	it.each([
		['counts plain prose', 'one two three', 3],
		['drops heading markers', '### Deep heading here', 3],
		['drops blockquote markers', '> quoted words here', 3],
		['drops bullet markers', '- one\n+ two\n* three', 3],
		['drops ordered list markers', '1. one\n2. two', 2],
		['drops task checkboxes', '- [ ] first task\n- [x] second task', 4],
		// escaped rather than literal, for the same reason as in noteStats.js
		['drops task checkboxes written with a non-breaking space', '- [\u00A0] lonely task', 2],
		['drops fenced code blocks', 'before\n```\nnot counted at all\n```\nafter', 2],
		['drops tilde fenced code blocks', 'before\n~~~\nnot counted at all\n~~~\nafter', 2],
		['drops images entirely', '![alt text here](image.png) caption', 1],
		['keeps link labels but not targets', '[the label](https://example.com/a/b/c)', 2],
		['keeps what inline code contains', '`inline` word', 2],
		['drops setext underlines', 'Title\n=====', 1],
		['drops thematic breaks', 'one\n\n---\n\ntwo', 2],
		['drops emphasis markers', '**bold words** and *italic* and ~~struck~~', 6],
	])('%s', (_label, content, expected) => {
		expect(words(content)).toBe(expected)
	})

	it.each([
		['an empty note', ''],
		['whitespace only', '   \n\n\t '],
		['markup that leaves nothing behind', '# \n\n---'],
		['a missing body', undefined],
		['a body that is not a string', 42],
	])('counts no words in %s', (_label, content) => {
		expect(words(content)).toBe(0)
	})
})

describe('noteTextStats reading time', () => {
	const note = (count) => Array.from({ length: count }, (_, index) => `word${index}`).join(' ')

	it('is zero for an empty note', () => {
		expect(noteTextStats('').readingMinutes).toBe(0)
	})

	it('never rounds a note that has words down to nothing', () => {
		expect(noteTextStats('three short words').readingMinutes).toBe(1)
	})

	it.each([
		[200, 1],
		[300, 2],
		[500, 3],
	])('reports %i words as %i minutes', (count, expected) => {
		expect(noteTextStats(note(count)).readingMinutes).toBe(expected)
	})
})
