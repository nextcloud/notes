/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext, APIResponse } from '@playwright/test'

import { expect, test } from '@playwright/test'

interface NoteData {
	id: number
	category: string
	internalPath: string
}

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

async function createNote(request: APIRequestContext, category: string): Promise<NoteData> {
	const response = await request.post('/index.php/apps/notes/api/v1/notes', {
		headers: authHeaders(),
		data: { title: `Playwright attachment ${Date.now()}`, category },
	})
	expect(response.ok(), 'creating the note').toBeTruthy()
	return response.json()
}

async function deleteNote(request: APIRequestContext, id: number): Promise<void> {
	const response = await request.delete(`/index.php/apps/notes/api/v1/notes/${id}`, { headers: authHeaders() })
	expect(response.ok(), `deleting note ${id}`).toBeTruthy()
}

async function uploadAttachment(request: APIRequestContext, noteId: number, name: string, content: string): Promise<string> {
	const response = await request.post(`/index.php/apps/notes/api/v1.4/attachment/${noteId}`, {
		headers: authHeaders(),
		multipart: { file: { name, mimeType: 'text/plain', buffer: Buffer.from(content) } },
	})
	expect(response.ok(), `uploading ${name}`).toBeTruthy()
	return (await response.json()).filename
}

function downloadAttachment(request: APIRequestContext, noteId: number, path: string): Promise<APIResponse> {
	return request.get(`/index.php/apps/notes/api/v1.4/attachment/${noteId}?path=${encodeURIComponent(path)}`, {
		headers: authHeaders(),
	})
}

/** Folder an uploaded attachment lives in over WebDAV, derived from the note's own path. */
function attachmentDavFolder(note: NoteData): string {
	const categoryDir = note.internalPath.slice(0, note.internalPath.lastIndexOf('/'))
	return `${categoryDir}/.attachments.${note.id}`
}

test.describe('Attachment API', () => {
	test('stores uploads under a note-specific folder with a readable, de-duplicated name', async ({ request }) => {
		const note = await createNote(request, `Playwright attachments ${Date.now()}`)

		const first = await uploadAttachment(request, note.id, 'photo.png', 'first')
		expect(first).toBe(`.attachments.${note.id}/photo.png`)

		const second = await uploadAttachment(request, note.id, 'photo.png', 'second')
		expect(second).toBe(`.attachments.${note.id}/photo (1).png`)

		const firstDownload = await downloadAttachment(request, note.id, first)
		expect(firstDownload.ok(), 'downloading the first upload').toBeTruthy()
		expect(await firstDownload.text()).toBe('first')

		const secondDownload = await downloadAttachment(request, note.id, second)
		expect(secondDownload.ok(), 'downloading the second upload').toBeTruthy()
		expect(await secondDownload.text()).toBe('second')

		await deleteNote(request, note.id)
	})

	test('moves the attachment folder when the note changes category', async ({ request }) => {
		const note = await createNote(request, `Playwright attachments A ${Date.now()}`)
		const path = await uploadAttachment(request, note.id, 'photo.png', 'content')

		const moved = await request.put(`/index.php/apps/notes/api/v1/notes/${note.id}`, {
			headers: authHeaders(),
			data: { category: `Playwright attachments B ${Date.now()}` },
		})
		expect(moved.ok(), 'moving the note to another category').toBeTruthy()

		const download = await downloadAttachment(request, note.id, path)
		expect(download.ok(), 'downloading the attachment after the category change').toBeTruthy()

		await deleteNote(request, note.id)
	})

	test('deletes the attachment folder when the note is deleted', async ({ request }) => {
		const note = await createNote(request, `Playwright attachments ${Date.now()}`)
		await uploadAttachment(request, note.id, 'photo.png', 'content')
		const filePath = davUrl(`${attachmentDavFolder(note)}/photo.png`)

		const beforeDelete = await request.get(filePath, { headers: authHeaders() })
		expect(beforeDelete.ok(), 'attachment file exists before note deletion').toBeTruthy()

		await deleteNote(request, note.id)

		const afterDelete = await request.get(filePath, { headers: authHeaders() })
		expect(afterDelete.status(), 'attachment file is gone after note deletion').toBe(404)
	})
})
