<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<EditorHint v-if="editorHint" @close="editorHint=false" />
	<NcContent v-else app-name="notes" :content-class="{loading: loading.notes}">
		<NcAppNavigation :class="{loading: loading.notes, 'icon-error': error}">
			<NcAppNavigationNew
				v-show="!loading.notes && !error"
				:text="t('notes', 'New category')"
				@click="onNewCategory"
				@dragover.native="onNewCategoryDragOver"
				@drop.native="onNewCategoryDrop"
			>
				<FolderPlusIcon slot="icon" :size="20" />
			</NcAppNavigationNew>

			<template #list>
				<CategoriesList v-show="!loading.notes" />
			</template>

			<template #footer>
				<ul class="app-navigation-entry__settings">
					<NcAppNavigationItem
						:name="t('notes', 'Notes settings')"
						@click.prevent="openSettings"
					>
						<CogIcon slot="icon" :size="20" />
					</NcAppNavigationItem>
				</ul>
				<AppSettings v-if="!loading.notes && error !== true" :open.sync="settingsVisible" @reload="reloadNotes" />
			</template>
		</NcAppNavigation>

		<NcAppContent v-if="error">
			<div style="margin: 2em;">
				<h2>{{ t('notes', 'Error') }}</h2>
				<p>{{ error }}</p>
				<p>{{ t('notes', 'Please see Nextcloud server log for details.') }}</p>
			</div>
		</NcAppContent>
		<router-view v-else @note-deleted="onNoteDeleted" />
	</NcContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcContent from '@nextcloud/vue/components/NcContent'
import { loadState } from '@nextcloud/initial-state'
import { showSuccess, TOAST_UNDO_TIMEOUT, TOAST_PERMANENT_TIMEOUT } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/style.css'
import { emit } from '@nextcloud/event-bus'

import CogIcon from 'vue-material-design-icons/CogOutline.vue'
import FolderPlusIcon from 'vue-material-design-icons/FolderPlus.vue'

import AppSettings from './components/AppSettings.vue'
import CategoriesList from './components/CategoriesList.vue'
import EditorHint from './components/Modal/EditorHint.vue'

import { config } from './config.js'
import { fetchNotes, noteExists, undoDeleteNote } from './NotesService.js'
import store from './store.js'

export default {
	name: 'App',

	components: {
		AppSettings,
		CategoriesList,
		CogIcon,
		EditorHint,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationNew,
		NcAppNavigationItem,
		NcContent,
		FolderPlusIcon,
	},

	data() {
		return {
			filter: {
				category: null,
			},
			loading: {
				notes: true,
			},
			error: false,
			undoNotification: null,
			undoTimer: null,
			deletedNotes: [],
			refreshTimer: null,
			editorHint: loadState('notes', 'editorHint', '') === 'yes' && window.OCA.Text?.createEditor,
			settingsVisible: false,
		}
	},

	computed: {
		numNotes() {
			return store.getters.numNotes()
		},

		notes() {
			return store.state.notes.notes
		},

		filteredNotes() {
			return store.getters.getFilteredNotes()
		},
	},

	created() {
		store.commit('setDocumentTitle', document.title)
		window.addEventListener('beforeunload', this.onClose)
		document.addEventListener('visibilitychange', this.onVisibilityChange)
		this.loadNotes()
	},

	destroyed() {
		document.removeEventListener('visibilitychange', this.onVisibilityChange)
		this.stopRefreshTimer()
	},

	methods: {
		loadNotes() {
			fetchNotes()
				.then(data => {
					if (data === null) {
						// nothing changed
						return
					}
					if (data.notes !== null) {
						this.error = false
						this.routeDefault(data.lastViewedNote)
					} else if (this.loading.notes) {
						// only show error state if not loading in background
						this.error = data.errorMessage
					} else {
						console.error('Server error while updating list of notes: ' + data.errorMessage)
					}
				})
				.catch(() => {
					// only show error state if not loading in background
					if (this.loading.notes) {
						this.error = true
					}
				})
				.then(() => {
					this.loading.notes = false
					this.startRefreshTimer(config.interval.notes.refresh)
				})
		},

		startRefreshTimer(seconds) {
			if (this.refreshTimer === null && document.visibilityState === 'visible') {
				this.refreshTimer = setTimeout(() => {
					this.refreshTimer = null
					this.loadNotes()
				}, seconds * 1000)
			}
		},

		stopRefreshTimer() {
			if (this.refreshTimer !== null) {
				clearTimeout(this.refreshTimer)
				this.refreshTimer = null
			}
		},

		onVisibilityChange() {
			if (document.visibilityState === 'visible') {
				this.startRefreshTimer(config.interval.notes.refreshAfterHidden)
			} else {
				this.stopRefreshTimer()
			}
		},

		reloadNotes() {
			if (this.$route.path !== '/') {
				this.$router.push('/')
			}
			store.commit('removeAllNotes')
			store.commit('clearSyncCache')
			this.loading.notes = true
			this.loadNotes()
		},

		routeDefault(defaultNoteId) {
			if (this.$route.name !== 'note' || !noteExists(this.$route.params.noteId)) {
				if (noteExists(defaultNoteId)) {
					this.routeToNote(defaultNoteId)
				} else {
					this.routeFirst()
				}
			}
		},

		routeFirst() {
			const availableNotes = this.filteredNotes.filter(note => !note.error && !note.deleting)
			if (availableNotes.length > 0) {
				this.routeToNote(availableNotes[0].id)
			} else {
				if (this.$route.name !== 'welcome') {
					this.$router.push({ name: 'welcome' })
				}
			}
		},

		routeToNote(id, query) {
			const noteId = id.toString()
			if (this.$route.name !== 'note' || this.$route.params.noteId !== noteId) {
				this.$router.push({
					name: 'note',
					params: { noteId },
					query,
				})
			}
		},

		openSettings() {
			this.settingsVisible = true
		},

		onNewCategory() {
			emit('notes:category:new')
		},

		onNewCategoryDragOver(event) {
			if (!this.isNoteDrag(event)) {
				return
			}
			event.preventDefault()
			if (event.dataTransfer) {
				event.dataTransfer.dropEffect = 'move'
			}
		},

		onNewCategoryDrop(event) {
			const noteId = this.getDraggedNoteId(event)
			if (noteId === null) {
				return
			}
			event.preventDefault()
			event.stopPropagation()
			emit('notes:category:new', { noteId })
		},

		getDraggedNoteId(event) {
			const dt = event?.dataTransfer
			if (!dt) {
				return null
			}

			const types = Array.from(dt.types ?? [])
			const hasCustom = types.includes('application/x-nextcloud-notes-note-id')
			const hasUri = types.includes('text/uri-list')
			if (!hasCustom && hasUri) {
				return null
			}

			let raw = ''
			if (hasCustom) {
				try {
					raw = dt.getData('application/x-nextcloud-notes-note-id')
				} catch {
					// Some browsers only allow specific mime types.
				}
			}
			if (!raw) {
				try {
					raw = dt.getData('text/plain')
				} catch {
					raw = ''
				}
			}

			const match = /^\s*(\d+)\s*$/.exec(raw)
			const parsedId = match ? Number.parseInt(match[1], 10) : Number.NaN
			if (!Number.isFinite(parsedId)) {
				return null
			}
			const note = store.getters.getNote(parsedId)
			if (!note || note.readonly) {
				return null
			}

			return parsedId
		},

		isNoteDrag(event) {
			const dt = event?.dataTransfer
			if (!dt) {
				return false
			}
			const types = Array.from(dt.types ?? [])
			if (types.includes('application/x-nextcloud-notes-note-id')) {
				return true
			}
			if (types.includes('text/uri-list')) {
				return false
			}
			try {
				return /^\s*\d+\s*$/.test(dt.getData('text/plain'))
			} catch {
				return false
			}
		},

		onNoteDeleted(note) {
			this.deletedNotes.push(note)
			this.clearUndoTimer()
			let label
			if (this.deletedNotes.length === 1) {
				label = this.t('notes', 'Deleted {title}', { title: note.title })
			} else {
				label = this.n('notes', 'Deleted {number} note', 'Deleted {number} notes', this.deletedNotes.length, { number: this.deletedNotes.length })
			}
			if (this.undoNotification === null) {
				const action = '<button class="undo">' + this.t('notes', 'Undo Delete') + '</button>'
				this.undoNotification = showSuccess(
					'<span class="deletedLabel">' + label + '</span> ' + action,
					{ isHTML: true, timeout: TOAST_PERMANENT_TIMEOUT, onRemove: this.onUndoNotificationClosed },
				)
				this.undoNotification.toastElement.getElementsByClassName('undo')
					.forEach(element => { element.onclick = this.onUndoDelete })
			} else {
				this.undoNotification.toastElement.getElementsByClassName('deletedLabel')
					.forEach(element => { element.textContent = label })
			}
			this.undoTimer = setTimeout(this.onRemoveUndoNotification, TOAST_UNDO_TIMEOUT)
			this.routeFirst()
		},

		clearUndoTimer() {
			if (this.undoTimer) {
				clearTimeout(this.undoTimer)
				this.undoTimer = null
			}
		},

		onUndoDelete() {
			const number = this.deletedNotes.length
			this.deletedNotes.forEach(note => undoDeleteNote(note))
			this.onRemoveUndoNotification()
			if (number === 1) {
				showSuccess(this.t('notes', 'Note recovered'))
			} else {
				showSuccess(this.n('notes', 'Recovered {number} note', 'Recovered {number} notes', number, { number }))
			}
		},

		onUndoNotificationClosed() {
			if (this.undoNotification) {
				this.undoNotification = null
				this.onRemoveUndoNotification()
			}
		},

		onRemoveUndoNotification() {
			this.deletedNotes = []
			if (this.undoNotification) {
				this.undoNotification.hideToast()
				this.undoNotification = null
			}
			this.clearUndoTimer()
		},

		onClose(event) {
			if (!this.notes.every(note => !note.unsaved)) {
				event.preventDefault()
				return this.t('notes', 'There are unsaved notes. Leaving the page will discard all changes!')
			}
		},
	},
}
</script>

<style scoped lang="scss">
// Source for footer fix: https://github.com/nextcloud/server/blob/master/apps/files/src/views/Navigation.vue
.app-navigation-entry__settings {
	height: auto !important;
	overflow: hidden !important;
	padding-top: 0 !important;
	// Prevent shrinking or growing
	flex: 0 0 auto;
	padding-inline-end: 3px;
	padding-bottom: 3px;
	padding-inline-start: 3px;
	margin: 0 3px;
}

:deep(.app-navigation__body) {
	overflow: hidden !important;
	flex: 0 0 auto;
}

:deep(.app-navigation__content) {
	min-height: 0;
}

:deep(.app-navigation__list) {
	flex: 1 1 auto;
	min-height: 0;
	height: auto !important;
}
</style>
