<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="text-editor-wrapper" :class="{ loading: loading, 'icon-error': !loading && (!note || note.error), 'is-mobile': isMobile }">
		<div v-show="!loading" ref="editor" class="text-editor" />
	</div>
</template>
<script>

import { useIsMobile } from '@nextcloud/vue/composables/useIsMobile'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'

import { queueCommand, refreshNote } from '../NotesService.js'
import { routeIsNewNote } from '../Util.js'
import store from '../store.js'

export default {
	name: 'NoteRich',

	props: {
		noteId: {
			type: String,
			required: true,
		},
	},

	setup() {
		return {
			isMobile: useIsMobile(),
		}
	},

	data() {
		return {
			loading: false,
			editor: null,
			shouldAutotitle: true,
		}
	},

	computed: {
		note() {
			return store.getters.getNote(parseInt(this.noteId))
		},
		isNewNote() {
			return routeIsNewNote(this.$route)
		},
	},

	watch: {
		$route(to, from) {
			if (to.name !== from.name || to.params.noteId !== from.params.noteId) {
				this.onClose(from.params.noteId)
				this.fetchData()
			}
		},
	},

	mounted() {
		this.fetchData()
		subscribe('files:node:updated', this.fileUpdated)
		subscribe('files_versions:restore:requested', this.onFileRestoreRequested)
		subscribe('files_versions:restore:restored', this.onFileRestored)
	},

	destroyed() {
		this?.editor?.destroy()
		unsubscribe('files:node:updated', this.fileUpdated)
		unsubscribe('files_versions:restore:requested', this.onFileRestoreRequested)
		unsubscribe('files_versions:restore:restored', this.onFileRestored)
	},

	methods: {
		async fetchData() {
			this.etag = null

			if (this.isMobile) {
				emit('toggle-navigation', { open: false })
			}

			this.loading = true

			await this.loadTextEditor()
		},

		async loadTextEditor() {
			if (!this.$refs?.editor) {
				await this.$nextTick()
			}
			this?.editor?.destroy()
			this.loading = true
			this.shouldAutotitle = undefined
			this.editor = (await window.OCA.Text.createEditor({
				el: this.$refs.editor,
				fileId: parseInt(this.noteId),
				filePath: this.note.internalPath,
				readOnly: false,
				onUpdate: ({ markdown }) => {
					if (this.note) {
						const unsaved = !!(this.note?.content && this.note.content !== markdown)
						if (this.shouldAutotitle === undefined) {
							const title = this.getTitle(markdown)
							this.shouldAutotitle = this.isNewNote || (title !== '' && title === this.note.title)
						}
						this.onEdit({ content: markdown, unsaved })
					}
				},
			}))
				.onLoaded(() => {
					this.loading = false
				})
		},

		onEdit(noteData = {}) {
			store.commit('updateNote', {
				...this.note,
				...noteData,
			})
		},

		onClose(noteId) {
			const note = store.getters.getNote(parseInt(noteId))
			store.commit('updateNote', {
				...note,
				unsaved: false,
			})
		},

		fileUpdated({ fileid }) {
			if (this.note.id === fileid) {
				this.onEdit({ unsaved: false })
				if (this.shouldAutotitle) {
					queueCommand(fileid, 'autotitle')
				}
			}
		},

		getTitle(content) {
			const firstLine = content.split('\n')[0] ?? ''
			const title = firstLine
				// See NoteUtil::sanitisePath
				.replaceAll(/^\s*[*+-]\s+/gmu, '')
				.replaceAll(/^[.\s]+/gmu, '')
				.replaceAll(/\*|\||\/|\\|:|"|'|<|>|\?/gmu, '')
				// See NoteUtil::stripMarkdown
				.replaceAll(/^#+\s+(.*?)\s*#*$/gmu, '$1')
				.replaceAll(/^(=+|-+)$/gmu, '')
				.replaceAll(/(\*+|_+)(.*?)\\1/gmu, '$2')
				.replaceAll(/\s/gmu, ' ')
			return title.length > 0 ? title : t('notes', 'New note')
		},

		async onFileRestoreRequested(event) {
			const { fileInfo } = event

			if (fileInfo.id !== this.note.id) {
				return
			}

			this.loading = true
		},

		async onFileRestored(version) {
			if (version.fileId !== this.note.id) {
				return
			}

			const etag = await refreshNote(parseInt(this.noteId), this.etag)

			if (etag) {
				this.etag = etag
			}

			const autoResolve = setInterval(() => {
				const el = document.querySelector('[data-cy="resolveServerVersion"]')

				if (el) {
					el.click()
					clearInterval(autoResolve)
				}
			}, 200)
			this.loading = false
		},
	},
}
</script>
<style lang="scss" scoped>
.text-editor-wrapper {
	height: 100%;
}

.text-editor {
	height: 100%;
}

.note-container {
	min-height: 100%;
	width: 100%;
	background-color: var(--color-main-background);
}

.is-mobile:deep(.text-menubar) {
	// Avoid overlapping the navigation toggle
	margin-inline-start: var(--default-clickable-area);
	z-index: 1;
}
</style>
