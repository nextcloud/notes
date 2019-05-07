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

			<ul v-show="!loading.notes">
				<!-- collapsible categories -->
				<AppNavigationItem
					v-if="notes.length"
					ref="categories"
					:item="categoryItem"
				/>

				<!-- search result header -->
				<li v-if="filter.search && filteredNotes.length" class="search-result-header">
					<a class="icon-search active">
						<span v-if="filter.category">
							{{ t('notes', 'Search result for “{search}” in {category}', { search: filter.search, category: filter.category }) }}
						</span>
						<span v-else>
							{{ t('notes', 'Search result for “{search}”', { search: filter.search }) }}
						</span>
					</a>
				</li>

				<!-- nothing found -->
				<li v-if="!filteredNotes.length">
					<span v-if="filter.search" class="nav-entry">
						<div id="emptycontent" class="emptycontent-search">
							<div class="icon-search" />
							<h2 v-if="filter.category">
								{{ t('notes', 'No search result for “{search}” in {category}', { search: filter.search, category: filter.category }) }}
							</h2>
							<h2 v-else>
								{{ t('notes', 'No search result for “{search}”', { search: filter.search }) }}
							</h2>
						</div>
					</span>
				</li>

				<!-- list of notes -->
				<template v-for="item in noteItems">
					<AppNavigationItem v-if="filter.category!==null && filter.category!==item.category"
						:key="item.category" :item="categoryToItem(item.category)"
					/>
					<NavigationNoteItem v-for="note in item.notes"
						:key="note.id" :note="note"
						@note-deleted="routeFirst"
					/>
				</template>
			</ul>

			<AppSettings v-if="!loading.notes && !error" @reload="reloadNotes" />
		</AppNavigation>

		<router-view />

		<router-view name="sidebar" />
	</Content>
</template>

<script>
import {
	AppNavigation,
	AppNavigationNew,
	AppNavigationItem,
	Content,
} from 'nextcloud-vue'
import AppSettings from './components/AppSettings'
import NavigationNoteItem from './components/NavigationNoteItem'
import NotesService from './NotesService'
import store from './store'

export default {
	name: 'App',

	components: {
		AppNavigation,
		AppNavigationNew,
		AppNavigationItem,
		AppSettings,
		Content,
		NavigationNoteItem,
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

		categories() {
			return NotesService.getCategories(1, true)
		},

		categoryItems() {
			let categories = this.categories
			let categoryItems = []
			categoryItems.push({
				text: this.t('notes', 'All notes'),
				icon: 'nav-icon-recent',
				action: this.onSelectCategory.bind(this, null),
				utils: {
					counter: this.notes.length,
				},
			})
			for (let i = 0; i < categories.length; i++) {
				let category = categories[i]
				let item = {
					text: NotesService.categoryLabel(category.name),
					icon: category.name === '' ? 'nav-icon-emptyfolder' : 'nav-icon-files',
					action: this.onSelectCategory.bind(this, category.name),
					utils: {
						counter: category.count,
					},
				}
				categoryItems.push(item)
			}
			return categoryItems
		},

		categoryItem() {
			return {
				text: this.filter.category === null ? this.t('notes', 'Categories') : NotesService.categoryLabel(this.filter.category),
				icon: 'nav-icon-files',
				collapsible: true,
				classes: 'app-navigation-noclose separator-below' + (this.filter.category === null ? '' : ' category-header'),
				children: this.categoryItems,
			}
		},

		filteredNotes() {
			let search = this.filter.search.toLowerCase()

			let notes = this.notes.filter(note => {
				const searchFields = [ 'title', 'category' ]
				if (this.filter.category !== null
					&& this.filter.category !== note.category
					&& !note.category.startsWith(this.filter.category + '/')) {
					return false
				}
				if (search !== '') {
					let found = false
					for (let j = 0; j < searchFields.length; j++) {
						let searchField = searchFields[j]
						if (note[searchField].toLowerCase().indexOf(search) !== -1) {
							found = true
							break
						}
					}
					if (!found) {
						return false
					}
				}
				return true
			})

			function cmpRecent(a, b) {
				if (a.favorite && !b.favorite) return -1
				if (!a.favorite && b.favorite) return 1
				return b.modified - a.modified
			}

			function cmpCategory(a, b) {
				let cmpCat = a.category.localeCompare(b.category)
				if (cmpCat !== 0) return cmpCat
				if (a.favorite && !b.favorite) return -1
				if (!a.favorite && b.favorite) return 1
				return a.title.localeCompare(b.title)
			}

			notes.sort(this.filter.category === null ? cmpRecent : cmpCategory)

			return notes
		},

		groupedNotes() {
			return this.filteredNotes.reduce(function(g, note) {
				if (g.length === 0 || g[g.length - 1].category !== note.category) {
					g.push({ category: note.category, notes: [] })
				}
				g[g.length - 1].notes.push(note)
				return g
			}, [])
		},

		noteItems() {
			if (this.filter.category == null) {
				return [ { notes: this.filteredNotes } ]
			} else {
				return this.groupedNotes
			}
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
					this.routeDefault(data.lastViewedNote)
				})
				.catch(() => {
					this.error = true
				})
				.finally(() => {
					this.loading.notes = false
				})
		},

		reloadNotes() {
			this.$router.push('/')
			store.commit('removeAll')
			this.loadNotes()
		},

		categoryToItem(category) {
			let label = '…/' + category.substring(this.filter.category.length + 1)
			return {
				isLabel: true,
				text: NotesService.categoryLabel(label),
				classes: 'app-navigation-caption app-navigation-noclose',
				icon: 'nav-icon-files',
				action: this.onSelectCategory.bind(this, category),
			}
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
			let availableNotes = this.filteredNotes.filter(note => !note.error)
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
			// TODO move the following from jQuery to plain JS
			if ($('#app-navigation-toggle').css('display') !== 'none' && !$('body').hasClass('snapjs-left')) {
				$('#app-navigation-toggle').click()
			}
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
			this.$refs.categories.toggleCollapse()
			this.filter.category = category
			// TODO move the following from jQuery to plain JS
			$('#app-navigation > ul').animate({ scrollTop: 0 }, 'fast')
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
<style scoped>
.content-wrapper {
	height: 100%;
}

.separator-below {
	border-bottom: 1px solid var(--color-border);
}

.search-result-header > a,
.search-result-header > a * {
	font-style: italic;
	cursor: default;
}

li .nav-entry .emptycontent-search {
	white-space: normal;
}

@media (max-height: 600px) {
	li .nav-entry .emptycontent-search {
		margin-top: inherit;
	}
}

</style>
