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
				<CategoriesList :loading="loading.notes" :hideNewCategoryAction="!!error" />
			</template>

			<template #footer>
				<ul class="app-navigation-entry__settings">
					<NcAppNavigationItem
						v-if="canUseZenMode"
						:name="t('notes', 'Zen mode')"
						:title="zenModeTitle"
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
		<!-- Wrapped in a div because NcButton's own scoped `position: relative` outranks any class we could add. -->
		<div v-if="zenMode" class="zen-controls">
			<NcButton variant="secondary"
				:title="shareTitle"
				:aria-label="shareTitle"
				@click="onOpenShareSidebar"
			>
				<template #icon>
					<ShareVariantOutlineIcon :size="20" />
				</template>
			</NcButton>
			<NcButton variant="secondary"
				:title="exitZenModeTitle"
				:aria-label="exitZenModeTitle"
				@click="onToggleZenMode"
			>
				<template #icon>
					<FocusIcon :size="20" />
				</template>
			</NcButton>
		</div>
		<NoteShareSidebar />
	</NcContent>
</template>

<script>
import { showSuccess, TOAST_PERMANENT_TIMEOUT, TOAST_UNDO_TIMEOUT } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { useIsMobile } from '@nextcloud/vue/composables/useIsMobile'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import CogIcon from 'vue-material-design-icons/CogOutline.vue'
import FocusIcon from 'vue-material-design-icons/ImageFilterCenterFocusStrongOutline.vue'
import ShareVariantOutlineIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import AppSettings from './components/AppSettings.vue'
import CategoriesList from './components/CategoriesList.vue'
import EditorHint from './components/Modal/EditorHint.vue'
import NoteShareSidebar from './components/NoteShareSidebar.vue'
import { config } from './config.js'
import logger from './Logger.js'
import { fetchNotes, noteExists, undoDeleteNote } from './NotesService.js'
import store from './store.js'

import '@nextcloud/dialogs/style.css'

const APPLE_PLATFORM = /mac|iphone|ipad|ipod/i
const OPEN_DIALOG_SELECTOR = '.modal-mask, .dialog__modal'

const isAppleDevice = APPLE_PLATFORM.test(navigator.userAgentData?.platform ?? navigator.platform ?? '')

export default {
	name: 'App',

	components: {
		AppSettings,
		CategoriesList,
		CogIcon,
		EditorHint,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcButton,
		NcContent,
		FocusIcon,
		NoteShareSidebar,
		ShareVariantOutlineIcon,
	},

	setup() {
		return {
			isMobile: useIsMobile(),
		}
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

		/* Mobile layouts have no chrome worth hiding and no room for the floating
		   controls, so zen mode stays out of their way. */
		canUseZenMode() {
			return !this.loading.notes && !this.error && this.$route.name === 'note' && !this.isMobile
		},

		zenModeShortcut() {
			return isAppleDevice ? 'Cmd + .' : 'Ctrl + .'
		},

		zenModeTitle() {
			return t('notes', 'Zen mode ({shortcut})', { shortcut: this.zenModeShortcut })
		},

		exitZenModeTitle() {
			return t('notes', 'Exit zen mode ({shortcut})', { shortcut: this.zenModeShortcut })
		},

		shareTitle() {
			return t('notes', 'Share')
		},
	},

	watch: {
		canUseZenMode(canUseZenMode) {
			if (!canUseZenMode) {
				store.app.setZenMode(false)
			}
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
						if (store.app.settings?.loadRecentOnStartUp) {
							this.routeDefault(data.lastViewedNote)
						} else {
							this.routeWelcome()
						}
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
				this.routeWelcome()
			}
		},

		routeWelcome() {
			if (this.$route.name !== 'welcome') {
				this.$router.push({ name: 'welcome' })
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

		onOpenShareSidebar() {
			emit('notes:share:open', { noteId: this.$route.params.noteId })
		},

		onKeyDown(event) {
			// Key repeat would toggle the mode over and over while the keys are held.
			if (event.repeat) {
				return
			}
			// `code` pins the physical key across layouts, `key` covers those with the period elsewhere.
			const isToggle = (event.ctrlKey || event.metaKey)
				&& (event.code === 'Period' || event.key === '.')
			const isExit = event.key === 'Escape' && this.zenMode
			if (!isToggle && !isExit) {
				return
			}
			if (isToggle && !this.canUseZenMode) {
				return
			}
			if (document.querySelector(OPEN_DIALOG_SELECTOR)) {
				return
			}
			event.preventDefault()
			if (isToggle) {
				this.onToggleZenMode()
			} else {
				store.app.setZenMode(false)
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
/* Not scoped: the hidden elements belong to @nextcloud/vue and to the server layout. */

/* Same approach as the viewer app: hide the header instead of removing it, so the
   layout below it never reflows. */
body:has(.notes-zen) #header {
	visibility: hidden;
}

/* Two nested boxes reserve room for the header and the rounded body container: the
   server's #content and NcContent's own .content. Both have to give up that room. */
#content:has(.notes-zen) {
	inset: 0;
	margin: 0;
	width: 100%;
	height: 100%;
	border-radius: 0;
}

/* The id outranks NcContent's scoped `.content[data-v-*]` rule, which is what sizes
   this box down to the header and the body container margin. */
#content-vue.notes-zen {
	width: 100%;
	height: 100%;
	padding-bottom: env(safe-area-inset-bottom, 0px);
	border-radius: 0;

	/* The still-present navigation sibling keeps granting the content rounded start
	   corners and a separator border. The id is needed to outrank that selector. */
	.app-content {
		border-inline-start: none;
		border-radius: 0;
	}
}

.notes-zen {
	.app-navigation,
	.app-navigation-toggle-wrapper,
	.splitpanes__pane-list,
	.splitpanes__splitter {
		display: none;
	}

	/* overrides the inline width splitpanes sets */
	.splitpanes__pane-details {
		width: 100% !important;
	}

	.note-editor {
		margin-inline: auto;
	}

	.note-container {
		padding-inline-end: 0;
	}
}

/* Bottom inline-start: the top belongs to NotePlain's action menu and the Text app's menubar. */
.zen-controls {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	position: fixed;
	bottom: calc(var(--default-grid-baseline) * 4);
	inset-inline-start: calc(var(--default-grid-baseline) * 4);
	z-index: 2000;
	opacity: 0.7;
	transition: opacity var(--animation-quick) ease-in-out;

	&:hover,
	&:focus-within {
		opacity: 1;
	}
}
</style>
