/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeAll, describe, expect, it, vi } from 'vitest'
import {
	categoryLabel,
	copyNote,
	escapeHtml,
	getDefaultSampleNote,
	getDefaultSampleNoteTitle,
	getDraggedNoteId,
	isNoteDrag,
	noteAttributes,
	routeIsNewNote,
} from '../Util.js'

const NOTE_ID_TYPE = 'application/x-nextcloud-notes-note-id'

/**
 * A drag event carrying the given data, as the browser would report it.
 *
 * @param {object} data mime type to payload
 * @param {Array<string>} [types] types to advertise, defaults to the data's keys
 * @return {object} something shaped like a DragEvent
 */
function dragEvent(data, types = Object.keys(data)) {
	return {
		dataTransfer: {
			types,
			getData: (type) => data[type] ?? '',
		},
	}
}

beforeAll(() => {
	// main.js hands the components t() from core's globals
	vi.stubGlobal('t', (app, text) => text)
})

describe('copyNote', () => {
	it('copies every note attribute', () => {
		const from = Object.fromEntries(noteAttributes.map((attr) => [attr, attr]))

		expect(copyNote(from, {})).toEqual(from)
	})

	it('leaves out what it is told to exclude', () => {
		const from = { id: 1, title: 'a', content: 'b' }

		expect(copyNote(from, {}, ['content'])).toStrictEqual({
			id: 1,
			title: 'a',
			etag: undefined,
			modified: undefined,
			favorite: undefined,
			category: undefined,
		})
	})

	it('returns the target it was given', () => {
		const to = {}

		expect(copyNote({ id: 1 }, to)).toBe(to)
	})
})

describe('categoryLabel', () => {
	it('names the empty category', () => {
		expect(categoryLabel('')).toBe('Uncategorized')
	})

	it('spaces out the separators of a nested category', () => {
		expect(categoryLabel('a/b/c')).toBe('a / b / c')
	})

	it('leaves a plain category alone', () => {
		expect(categoryLabel('Recipes')).toBe('Recipes')
	})
})

describe('routeIsNewNote', () => {
	it.each([
		['the query carries new', { query: { new: null } }, true],
		['the query carries other keys', { query: { other: '1' } }, false],
		['the query is empty', { query: {} }, false],
	])('is %s', (_label, route, expected) => {
		expect(routeIsNewNote(route)).toBe(expected)
	})
})

describe('isNoteDrag', () => {
	it('recognises the note id type', () => {
		expect(isNoteDrag(dragEvent({ [NOTE_ID_TYPE]: '7' }))).toBe(true)
	})

	it('takes a bare note id as a note', () => {
		expect(isNoteDrag(dragEvent({ 'text/plain': ' 7 ' }))).toBe(true)
	})

	it.each([
		['there is no data transfer', {}],
		['the transfer advertises no types', { dataTransfer: { getData: () => '' } }],
		['a link is dragged', dragEvent({ 'text/uri-list': 'https://example.com' })],
		['the text is not a note id', dragEvent({ 'text/plain': 'some words' })],
	])('says no when %s', (_label, event) => {
		expect(isNoteDrag(event)).toBe(false)
	})

	it('says no when the browser refuses the data', () => {
		const event = dragEvent({ 'text/plain': '7' })
		event.dataTransfer.getData = () => {
			throw new Error('not allowed')
		}

		expect(isNoteDrag(event)).toBe(false)
	})
})

describe('getDraggedNoteId', () => {
	const writableNote = () => ({ readonly: false })

	it('reads the id from the note id type', () => {
		expect(getDraggedNoteId(dragEvent({ [NOTE_ID_TYPE]: '7' }), writableNote)).toBe(7)
	})

	it('falls back to the plain text id', () => {
		expect(getDraggedNoteId(dragEvent({ 'text/plain': '7' }), writableNote)).toBe(7)
	})

	it('falls back to the plain text id when the browser refuses the custom type', () => {
		const event = dragEvent({ 'text/plain': '7' }, [NOTE_ID_TYPE, 'text/plain'])
		const getData = event.dataTransfer.getData
		event.dataTransfer.getData = (type) => {
			if (type === NOTE_ID_TYPE) {
				throw new Error('not allowed')
			}
			return getData(type)
		}

		expect(getDraggedNoteId(event, writableNote)).toBe(7)
	})

	it('drops the drag when the browser refuses the plain text too', () => {
		const event = dragEvent({}, ['text/plain'])
		event.dataTransfer.getData = () => {
			throw new Error('not allowed')
		}

		expect(getDraggedNoteId(event, writableNote)).toBeNull()
	})

	it('asks about the note it read', () => {
		const getNoteById = vi.fn(writableNote)

		getDraggedNoteId(dragEvent({ [NOTE_ID_TYPE]: '7' }), getNoteById)

		expect(getNoteById).toHaveBeenCalledWith(7)
	})

	it.each([
		['there is no data transfer', {}, writableNote],
		['the transfer advertises no types', { dataTransfer: { getData: () => '' } }, writableNote],
		['a link is dragged', dragEvent({ 'text/uri-list': 'https://example.com' }), writableNote],
		['the id is not a number', dragEvent({ [NOTE_ID_TYPE]: 'seven' }), writableNote],
		['the note is unknown', dragEvent({ [NOTE_ID_TYPE]: '7' }), () => null],
		['the note is read-only', dragEvent({ [NOTE_ID_TYPE]: '7' }), () => ({ readonly: true })],
		['no lookup was given', dragEvent({ [NOTE_ID_TYPE]: '7' }), undefined],
	])('drops the drag when %s', (_label, event, getNoteById) => {
		expect(getDraggedNoteId(event, getNoteById)).toBeNull()
	})
})

describe('getDefaultSampleNote', () => {
	it('opens with the sample note title as a heading', () => {
		expect(getDefaultSampleNote()).toMatch(new RegExp(`^# ${getDefaultSampleNoteTitle()}\n`))
	})

	it('carries the task list the sample is meant to show off', () => {
		expect(getDefaultSampleNote()).toContain('* [ ] ')
	})
})

describe('escapeHtml', () => {
	it.each([
		['<script>alert(1)</script>', '&lt;script&gt;alert(1)&lt;/script&gt;'],
		['a & b', 'a &amp; b'],
		['plain text', 'plain text'],
	])('escapes %j', (input, expected) => {
		expect(escapeHtml(input)).toBe(expected)
	})
})
