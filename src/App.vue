<template>
	<Content app-name="notes" :content-class="{loading: loading.notes}">
		<AppNavigation :class="{loading: loading.notes, 'icon-error': error}">
			<AppNavigationNew
				v-show="!loading.notes && !error"
				:text="t('notes', 'New note')"
				button-id="notes_new_note"
				:button-class="['icon-add', { loading: loading.create }]"
				@click="onNewNote"
			/>

			<template #list>
				<NavigationList v-show="!loading.notes"
					:filtered-notes="filteredNotes"
					:category="filter.category"
					:search="filter.search"
					@category-selected="onSelectCategory"
					@note-deleted="onNoteDeleted"
				/>
			</template>

			<template #footer>
				<AppSettings v-if="!loading.notes && error !== true" @reload="reloadNotes" />
			</template>
		</AppNavigation>

		<AppContent v-if="error">
			<div style="margin: 2em;">
				<h2>{{ t('notes', 'Error') }}</h2>
				<p>{{ error }}</p>
				<p>{{ t('notes', 'Please see Nextcloud server log for details.') }}</p>
			</div>
		</AppContent>
		<router-view v-else />

		<router-view name="sidebar" />
	</Content>
</template>

<script>
import {
	AppContent,
	AppNavigation,
	AppNavigationNew,
	Content,
} from '@nextcloud/vue'
import { showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'

import { config } from './config'
import { fetchNotes, noteExists, createNote, undoDeleteNote } from './NotesService'
import AppSettings from './components/AppSettings'
import NavigationList from './components/NavigationList'
import store from './store'

export default {
	name: 'App',

	components: {
		AppContent,
		AppNavigation,
		AppNavigationNew,
		AppSettings,
		Content,
		NavigationList,
	},

	data() {
		return {
			filter: {
				category: null,
				search: '',
			},
			loading: {
				notes: true,
				create: false,
			},
			error: false,
			undoNotification: null,
			undoTimer: null,
			deletedNotes: [],
		}
	},

	computed: {
		notes() {
			return store.state.notes.notes
		},

		filteredNotes() {
			const search = this.filter.search.toLowerCase()

			const notes = this.notes.filter(note => {
				if (this.filter.category !== null
					&& this.filter.category !== note.category
					&& !note.category.startsWith(this.filter.category + '/')) {
					return false
				}
				const searchFields = ['title', 'category']
				if (search !== '') {
					return searchFields.some(
						searchField => note[searchField].toLowerCase().indexOf(search) !== -1
					)
				}
				return true
			})

			function cmpRecent(a, b) {
				if (a.favorite && !b.favorite) return -1
				if (!a.favorite && b.favorite) return 1
				return b.modified - a.modified
			}

			function cmpCategory(a, b) {
				const cmpCat = a.category.localeCompare(b.category)
				if (cmpCat !== 0) return cmpCat
				if (a.favorite && !b.favorite) return -1
				if (!a.favorite && b.favorite) return 1
				return a.title.localeCompare(b.title)
			}

			notes.sort(this.filter.category === null ? cmpRecent : cmpCategory)

			return notes
		},
	},

	created() {
		store.commit('setDocumentTitle', document.title)
		this.search = new OCA.Search(this.onSearch, this.onResetSearch)
		window.addEventListener('beforeunload', this.onClose)
		this.loadNotes()
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
					setTimeout(this.loadNotes, config.interval.notes.refresh * 1000)
				})
		},

		reloadNotes() {
			if (this.$route.path !== '/') {
				this.$router.push('/')
			}
			store.commit('removeAllNotes')
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

		routeToNote(noteId, query) {
			if (this.$route.name !== 'note' || this.$route.params.noteId !== noteId.toString()) {
				this.$router.push({
					name: 'note',
					params: { noteId: noteId.toString() },
					query,
				})
			}
		},

		onSearch(query) {
			this.filter.search = query

			emit('toggle-navigation', { open: true })
		},

		onResetSearch() {
			this.filter.search = ''
		},

		onNewNote() {
			if (this.loading.create) {
				return
			}
			this.loading.create = true
			createNote(this.filter.category || '')
				.then(note => {
					this.routeToNote(note.id, { new: null })
				})
				.catch(() => {
				})
				.then(() => {
					this.loading.create = false
				})
		},

		onSelectCategory(category) {
			this.filter.category = category

			const appNavigation = document.querySelector('#app-navigation > ul')
			if (appNavigation) {
				appNavigation.scrollTop = 0
			}
		},

		onNoteDeleted(note) {
			this.deletedNotes.push(note)
			this.clearUndoTimer()
			let label
			if (this.deletedNotes.length === 1) {
				label = this.t('notes', 'Deleted {title}', { title: note.title })
			} else {
				label = this.t('notes', 'Deleted {number} notes', { number: this.deletedNotes.length })
			}
			if (this.undoNotification === null) {
				const action = '<button class="undo">' + this.t('notes', 'Undo Delete') + '</button>'
				this.undoNotification = showSuccess(
					'<span class="deletedLabel">' + label + '</span> ' + action,
					{ isHTML: true, timeout: -1, onRemove: this.onUndoNotificationClosed }
				)
				this.undoNotification.toastElement.getElementsByClassName('undo')
					.forEach(element => { element.onclick = this.onUndoDelete })
			} else {
				this.undoNotification.toastElement.getElementsByClassName('deletedLabel')
					.forEach(element => { element.textContent = label })
			}
			this.undoTimer = setTimeout(this.onRemoveUndoNotification, 12000)
			this.routeFirst()
		},

		clearUndoTimer() {
			if (this.undoTimer) {
				clearTimeout(this.undoTimer)
				this.undoTimer = null
			}
		},

		onUndoDelete() {
			this.deletedNotes.forEach(note => undoDeleteNote(note))
			this.onRemoveUndoNotification()
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
