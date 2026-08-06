<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<EditorHint v-if="editorHint" @close="editorHint = false" />
	<NcContent v-else
		appName="notes"
		:class="{ 'notes-zen': zenMode }"
		:contentClass="{loading: loading.notes}"
	>
		<NcAppNavigation :class="{loading: loading.notes, 'icon-error': error}">
			<template #list>
				<NcAppNavigationNew
					v-show="!loading.notes && !error"
					:text="t('notes', 'New category')"
					@click="onNewCategory"
					@dragover="onNewCategoryDragOver"
					@drop="onNewCategoryDrop"
				>
					<template #icon>
						<FolderPlusIcon :size="20" />
					</template>
				</NcAppNavigationNew>
				<CategoriesList :loading="loading.notes" />
			</template>

			<template #footer>
				<ul class="app-navigation-entry__settings">
					<NcAppNavigationItem
						v-if="!loading.notes && !error"
						:name="t('notes', 'Zen mode')"
						:title="t('notes', 'CTRL + .')"
						@click.prevent="onToggleZenMode"
					>
						<template #icon>
							<FocusIcon :size="20" />
						</template>
					</NcAppNavigationItem>
					<NcAppNavigationItem
						:name="t('notes', 'Notes settings')"
						@click.prevent="openSettings"
					>
						<template #icon>
							<CogIcon :size="20" />
						</template>
					</NcAppNavigationItem>
				</ul>
				<AppSettings v-if="!loading.notes && error !== true" v-model:open="settingsVisible" @reload="reloadNotes" />
			</template>
		</NcAppNavigation>

		<NcAppContent v-if="error">
			<div style="margin: 2em;">
				<h2>{{ t('notes', 'Error') }}</h2>
				<p>{{ error }}</p>
				<p>{{ t('notes', 'Please see Nextcloud server log for details.') }}</p>
			</div>
		</NcAppContent>
		<router-view v-else @noteDeleted="onNoteDeleted" />
		<NcButton v-if="zenMode"
			class="zen-exit"
			variant="secondary"
			:title="t('notes', 'CTRL + .')"
			:aria-label="t('notes', 'Exit zen mode')"
			@click="onToggleZenMode"
		>
			<template #icon>
				<FocusIcon :size="20" />
			</template>
		</NcButton>
		<NoteShareSidebar />
	</NcContent>
</template>

<script>
import { showSuccess, TOAST_PERMANENT_TIMEOUT, TOAST_UNDO_TIMEOUT } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import CogIcon from 'vue-material-design-icons/CogOutline.vue'
import FolderPlusIcon from 'vue-material-design-icons/FolderPlusOutline.vue'
import FocusIcon from 'vue-material-design-icons/ImageFilterCenterFocusStrongOutline.vue'
import AppSettings from './components/AppSettings.vue'
import CategoriesList from './components/CategoriesList.vue'
import EditorHint from './components/Modal/EditorHint.vue'
import NoteShareSidebar from './components/NoteShareSidebar.vue'
import { config } from './config.js'
import logger from './Logger.js'
import { fetchNotes, noteExists, undoDeleteNote } from './NotesService.js'
import store from './store.js'
import { getDraggedNoteId, isNoteDrag } from './Util.js'

import '@nextcloud/dialogs/style.css'

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
		NcButton,
		NcContent,
		FocusIcon,
		NoteShareSidebar,
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
			return store.notes.numNotes()
		},

		notes() {
			return store.notes.notes
		},

		filteredNotes() {
			return store.notes.getFilteredNotes()
		},

		zenMode() {
			return store.app.zenMode
		},
	},

	created() {
		store.app.setDocumentTitle(document.title)
		window.addEventListener('beforeunload', this.onClose)
		document.addEventListener('visibilitychange', this.onVisibilityChange)
		document.addEventListener('keydown', this.onKeyDown)
		this.loadNotes()
	},

	unmounted() {
		document.removeEventListener('visibilitychange', this.onVisibilityChange)
		document.removeEventListener('keydown', this.onKeyDown)
		this.stopRefreshTimer()
	},

	methods: {
		loadNotes() {
			fetchNotes()
				.then((data) => {
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
						logger.error('Server error while updating list of notes', { errorMessage: data.errorMessage })
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
			store.notes.removeAllNotes()
			store.sync.clearSyncCache()
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
			const availableNotes = this.filteredNotes.filter((note) => !note.error && !note.deleting)
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

		onToggleZenMode() {
			store.app.toggleZenMode()
		},

		onKeyDown(event) {
			// Ctrl + . toggles, Escape only ever leaves. Both are ignored while a
			// dialog is open so it can keep Escape for closing itself.
			if (document.querySelector('.modal-mask, .dialog__modal')) {
				return
			}
			if ((event.ctrlKey || event.metaKey) && event.key === '.') {
				event.preventDefault()
				this.onToggleZenMode()
			} else if (event.key === 'Escape' && this.zenMode) {
				event.preventDefault()
				store.app.setZenMode(false)
			}
		},

		onNewCategory() {
			emit('notes:category:new')
		},

		onNewCategoryDragOver(event) {
			if (!isNoteDrag(event)) {
				return
			}
			event.preventDefault()
			if (event.dataTransfer) {
				event.dataTransfer.dropEffect = 'move'
			}
		},

		onNewCategoryDrop(event) {
			const noteId = getDraggedNoteId(event, (noteId) => store.notes.getNote(noteId))
			if (noteId === null) {
				return
			}
			event.preventDefault()
			event.stopPropagation()
			emit('notes:category:new', { noteId })
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
				const undoButton = this.undoNotification.toastElement.querySelector('.undo')
				if (undoButton) {
					undoButton.onclick = this.onUndoDelete
				}
			} else {
				const deletedLabel = this.undoNotification.toastElement.querySelector('.deletedLabel')
				if (deletedLabel) {
					deletedLabel.textContent = label
				}
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
			this.deletedNotes.forEach((note) => undoDeleteNote(note))
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
			if (!this.notes.every((note) => !note.unsaved)) {
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

<style lang="scss">
/* Intentionally not scoped: zen mode hides DOM that belongs to @nextcloud/vue —
   the navigation, and the note-list pane of NcAppContent's split view — which a
   scoped style cannot reach. */
.notes-zen {
	.app-navigation,
	.app-navigation-toggle-wrapper,
	.splitpanes__pane-list,
	.splitpanes__splitter {
		display: none;
	}

	/* the details pane is sized by splitpanes via an inline width */
	.splitpanes__pane-details {
		width: 100% !important;
	}

	/* Centre the note at any window width. Outside zen mode this only happens
	   above 1600px, where there is room for the list next to it. */
	.note-editor {
		margin-inline: auto;
	}

	/* the large-screen rule reserves space on the right for the action menu;
	   with the panes gone the note would sit visibly off-centre */
	.note-container {
		padding-inline-end: 0;
	}
}

/* Bottom corner on purpose: the top of the editor is taken by NotePlain's
   action menu and, in rich mode, by the Text app's sticky menubar. */
.zen-exit {
	position: fixed;
	bottom: calc(var(--default-grid-baseline) * 4);
	inset-inline-end: calc(var(--default-grid-baseline) * 4);
	z-index: 2000;
	opacity: 0.6;

	&:hover,
	&:focus-visible {
		opacity: 1;
	}
}
</style>
