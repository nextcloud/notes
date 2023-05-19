<template>
	<EditorHint v-if="editorHint" @close="editorHint=false" />
	<NcContent v-else app-name="notes" :content-class="{loading: loading.notes}">
		<NcAppNavigation :class="{loading: loading.notes, 'icon-error': error}">
			<NcAppNavigationNew
				v-show="!loading.notes && !error"
				:text="t('notes', 'New note')"
				@click="onNewNote"
			>
				<PlusIcon slot="icon" :size="20" />
			</NcAppNavigationNew>

			<template #list>
				<CategoriesList v-show="!loading.notes"
					v-if="numNotes"
				/>
			</template>

			<template #footer>
				<ul class="app-navigation-entry__settings">
					<NcAppNavigationItem
						:title="t('notes', 'Notes settings')"
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

		<router-view name="sidebar" />
	</NcContent>
</template>

<script>
import {
	NcAppContent,
	NcAppNavigation,
	NcAppNavigationNew,
	NcAppNavigationItem,
	NcContent,
} from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'
import { showSuccess, TOAST_UNDO_TIMEOUT, TOAST_PERMANENT_TIMEOUT } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/dist/index.css'

import PlusIcon from 'vue-material-design-icons/Plus.vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'

import AppSettings from './components/AppSettings.vue'
import CategoriesList from './components/CategoriesList.vue'
import EditorHint from './components/Modal/EditorHint.vue'

import { config } from './config.js'
import { fetchNotes, noteExists, createNote, undoDeleteNote } from './NotesService.js'
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
		PlusIcon,
	},

	data() {
		return {
			filter: {
				category: null,
			},
			loading: {
				notes: true,
				create: false,
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

		onNewNote() {
			if (this.loading.create) {
				return
			}
			this.loading.create = true
			createNote(store.getters.getSelectedCategory())
				.then(note => {
					this.routeToNote(note.id, { new: null })
				})
				.catch(() => {
				})
				.finally(() => {
					this.loading.create = false
				})
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
					{ isHTML: true, timeout: TOAST_PERMANENT_TIMEOUT, onRemove: this.onUndoNotificationClosed }
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
	padding-right: 3px;
	padding-bottom: 3px;
	padding-left: 3px;
	margin: 0 3px;
}
</style>
