<template>
	<app-content app-name="notes" :navigation-class="{loading: loading}" :content-class="{loading: loading}">
		<template #navigation>

			<app-navigation-new
				v-show="!loading"
				:text="t('notes', 'New note')"
				button-id="notes_new_note"
				button-class="icon-add"
				@click="onNewNote"
			/>

			<ul v-show="!loading">

				<!-- collapsible categories -->
				<app-navigation-item
					v-if="notes.length"
					ref="categories"
					:item="categoryItem"
				/>

				<!-- search result header -->
				<li v-if="filter.search && noteItems.length" class="search-result-header">
					<a class="nav-icon-search active">
						<span v-if="filter.category">
							{{ t('notes', 'Search result for “{search}” in {category}', { search: filter.search, category: filter.category }) }}
						</span>
						<span v-else>
							{{ t('notes', 'Search result for “{search}”', { search: filter.search }) }}
						</span>
					</a>
				</li>

				<!-- nothing found -->
				<li v-if="!noteItems.length">
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
				<app-navigation-item v-for="item in noteItems"
					:key="item.key"
					:item="item"
				/>

			</ul>

			<app-settings v-show="!loading" />

		</template>

		<template #content>
			<router-view />
		</template>

	</app-content>
</template>

<script>
import {
	AppContent,
	AppNavigationNew,
	AppNavigationItem,
} from 'nextcloud-vue'
import AppSettings from './AppSettings'
import NotesService from './NotesService'
import store from './store'

export default {
	name: 'App',

	components: {
		AppContent,
		AppNavigationNew,
		AppNavigationItem,
		AppSettings,
	},

	data: function() {
		return {
			filter: {
				category: null,
				search: '',
			},
			loading: false,
		}
	},

	computed: {
		notes() {
			return store.state.notes
		},

		categories() {
			return store.getters.getCategories(1, true)
		},

		categoryItems() {
			let categories = this.categories
			let categoryItems = []
			categoryItems.push({
				text: t('notes', 'All notes'),
				icon: 'nav-icon-recent',
				action: this.onSelectCategory.bind(this, null),
				utils: {
					counter: this.notes.length,
				},
			})
			for (let i = 0; i < categories.length; i++) {
				let category = categories[i]
				let item = {
					text: this.categoryLabel(category.name),
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
				text: this.filter.category === null ? t('notes', 'Categories') : this.categoryLabel(this.filter.category),
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
				if (a.modified > b.modified) return -1
				if (a.modified < b.modified) return 1
				return 0
			}

			function cmpCategory(a, b) {
				if (a.category < b.category) return -1
				if (a.category > b.category) return 1
				if (a.favorite && !b.favorite) return -1
				if (!a.favorite && b.favorite) return 1
				if (a.title < b.title) return -1
				if (a.title > b.title) return 1
				return 0
			}

			notes.sort(this.filter.category === null ? cmpRecent : cmpCategory)

			return notes
		},

		noteItems() {
			let items = []
			let prevCat = null
			for (let i = 0; i < this.filteredNotes.length; i++) {
				let note = this.filteredNotes[i]
				if (this.filter.category !== null && prevCat !== null && prevCat !== note.category) {
					let category = '…/' + note.category.substring(this.filter.category.length + 1)
					items.push({
						text: this.categoryLabel(category),
						classes: 'app-navigation-caption app-navigation-noclose',
						icon: 'nav-icon-files',
						action: this.onSelectCategory.bind(this, note.category),
					})
				}
				items.push({
					text: note.title,
					iconClass: 'nav-icon ' + (note.favorite ? 'icon-notes-starred' : 'icon-notes-star'),
					iconAction: this.onFavorite.bind(null, note.id, !note.favorite),
					iconTitle: t('notes', 'Favorite'),
					router: {
						name: 'note',
						params: {
							noteId: note.id,
						},
					},
					utils: {
						actions: [
							{
								text: t('notes', 'Delete note'),
								icon: 'icon-delete only-active-hover',
								action: this.onDeleteNote.bind(null, note.id),
							},
						],
					},
				})
				prevCat = note.category
			}
			return items
		},
	},

	created() {
		this.loading = true
		NotesService.fetchNotes()
			.then(() => { this.loading = false })
		this.search = new OCA.Search(this.onSearch, this.onResetSearch)
	},

	methods: {
		onSearch(query) {
			this.filter.search = query
			/* TODO open navigation on small screens
			if($('#app-navigation-toggle').css('display')!=='none' &&
			  !$('body').hasClass('snapjs-left')) {
				$('#app-navigation-toggle').click()
			}
			*/
		},
		onResetSearch() {
			this.filter.search = ''
		},
		onNewNote() {
			// TODO create new note
		},
		onFavorite(noteId, favorite) {
			NotesService.setFavorite(noteId, favorite)
			// TODO set favorite for note, and update
		},
		onDeleteNote(noteId) {
			// TODO delete note (with undo)
		},
		onSelectCategory(category) {
			this.$refs.categories.toggleCollapse()
			this.filter.category = category
		},
		categoryLabel(category) {
			return category === '' ? t('notes', 'Uncategorized') : category.replace(/\//g, ' / ')
		},
		testMethod() {
			console.debug('Test Method')
		},
	},
}
</script>
<style scoped>
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
