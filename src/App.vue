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

			<NavigationList v-show="!loading.notes"
				:filtered-notes="filteredNotes"
				:category="filter.category"
				:search="filter.search"
				@category-selected="onSelectCategory"
				@note-deleted="routeFirst"
			/>

			<AppSettings v-if="!loading.notes && error !== true" @reload="reloadNotes" />
		</AppNavigation>

		<AppContent v-if="error">
			<div style="margin: 2em;">
				<h2>{{ t('notes', 'Error') }}</h2>
				<p>{{ error }}</p>
				<p>{{ t('notes', 'Please chose a valid path in {label} (bottom left corner).', { label: t('notes', 'Settings') }) }}</p>
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
import AppSettings from './components/AppSettings'
import NavigationList from './components/NavigationList'
import NotesService from './NotesService'
import store from './store'
import { openNavbar } from './nextcloud'

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

	data: function() {
		return {
			filter: {
				category: null,
				search: '',
			},
			loading: {
				notes: false,
				create: false,
			},
			error: false,
		}
	},

	computed: {
		notes() {
			return store.state.notes
		},

		filteredNotes() {
			const search = this.filter.search.toLowerCase()

			const notes = this.notes.filter(note => {
				if (note.deleting === 'deleting') {
					return false
				}
				if (this.filter.category !== null
					&& this.filter.category !== note.category
					&& !note.category.startsWith(this.filter.category + '/')) {
					return false
				}
				const searchFields = [ 'title', 'category' ]
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
			this.loading.notes = true
			NotesService.fetchNotes()
				.then(data => {
					if (data.notes !== null) {
						this.error = false
						this.routeDefault(data.lastViewedNote)
					} else {
						this.error = data.errorMessage
					}
				})
				.catch(() => {
					this.error = true
				})
				.finally(() => {
					this.loading.notes = false
				})
		},

		reloadNotes() {
			if (this.$route.path !== '/') {
				this.$router.push('/')
			}
			store.commit('removeAll')
			this.loadNotes()
		},

		routeDefault(defaultNoteId) {
			if (this.$route.name !== 'note' || !NotesService.noteExists(this.$route.params.noteId)) {
				if (NotesService.noteExists(defaultNoteId)) {
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
				this.$router.push({ name: 'welcome' })
			}
		},

		routeToNote(noteId) {
			this.$router.push({
				name: 'note',
				params: { noteId: noteId.toString() },
			})
		},

		onSearch(query) {
			this.filter.search = query

			openNavbar()
		},

		onResetSearch() {
			this.filter.search = ''
		},

		onNewNote() {
			if (this.loading.create) {
				return
			}
			this.loading.create = true
			NotesService.createNote(this.filter.category)
				.then(note => {
					this.routeToNote(note.id)
				})
				.catch(() => {
				})
				.finally(() => {
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

		onClose(event) {
			if (!this.notes.every(note => !note.unsaved)) {
				event.preventDefault()
				return this.t('notes', 'There are unsaved notes. Leaving the page will discard all changes!')
			}
		},
	},
}
</script>
