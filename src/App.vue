<template>
	<app-content app-name="notes">
		<template slot="navigation">
			<app-navigation-new
				v-if="!loading"
				:text="t('notes', 'New note')"
				button-id="notes_new_note"
				button-class="icon-add"
				@click="onNewNote"
			/>
			<ul v-if="!loading">
				<app-navigation-item
					v-if="notes.length"
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

				<app-navigation-item v-for="item in noteItems"
					:key="item.key"
					:item="item"
				/>
			</ul>
			<app-navigation-settings v-if="!loading" :title="t('notes', 'Settings')">
				TODO: settings
			</app-navigation-settings>
		</template>
		<template slot="content">
			<router-view />
		</template>
	</app-content>
</template>

<script>
import {
	AppContent,
	AppNavigationNew,
	AppNavigationItem,
	AppNavigationSettings,
} from 'nextcloud-vue'
import NotesService from './NotesService'
import store from './store'

export default {
	name: 'App',

	components: {
		AppContent,
		AppNavigationNew,
		AppNavigationItem,
		AppNavigationSettings,
	},

	data: function() {
		return {
			filter: {
				category: null,
				search: '',
			},
		}
	},

	computed: {
		notes() {
			return store.state.notes
		},
		loading() {
			return store.state.loading
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
		noteItems() {
			let items = []
			let search = this.filter.search.toLowerCase()
			const searchFields = [ 'title', 'category' ]
			for (let i = 0; i < this.notes.length; i++) {
				let note = this.notes[i]
				if (this.filter.category !== null && this.filter.category !== note.category) {
					continue
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
						continue
					}
				}
				let item = {
					text: note.title,
					router: {
						name: 'note',
						params: {
							noteId: note.id,
						},
					},
				}
				items.push(item)
			}
			return items
		},
	},

	created() {
		NotesService.fetchNotes()
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
		onSelectCategory(category) {
			this.filter.category = category
		},
		categoryLabel(category) {
			return category === '' ? t('notes', 'Uncategorized') : category
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
