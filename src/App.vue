<template>
	<AppContent app-name="notes">
		<template slot="navigation">
			<AppNavigationNew :text="t('notes', 'New note')"
				button-id="notes_new_note"
				button-class="icon-add"
				@click="onNewNote"
			/>
			<ul>
				<AppNavigationItem
					:item="categoryItem"
				/>
				<AppNavigationItem v-for="item in noteItems"
					:key="item.key"
					:item="item"
				/>
			</ul>
			<AppNavigationSettings :title="t('notes', 'Settings')">
				TODO: settings
			</AppNavigationSettings>
		</template>
		<template slot="content">
			TODO: content
		</template>
	</AppContent>
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
			},
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
				action: this.selectCategory.bind(this, null),
				utils: {
					counter: this.notes.length,
				},
			})
			for (let i = 0; i < categories.length; i++) {
				let category = categories[i]
				let item = {
					text: this.categoryLabel(category.name),
					icon: category.name === '' ? 'nav-icon-emptyfolder' : 'nav-icon-files',
					action: this.selectCategory.bind(this, category.name),
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
			for (let i = 0; i < this.notes.length; i++) {
				let note = this.notes[i]
				if (this.filter.category !== null && this.filter.category !== note.category) {
					continue
				}
				let item = { text: note.title }
				items.push(item)
			}
			return items
		},
	},
	created() {
		NotesService.fetchNotes()
	},
	methods: {
		onNewNote() {
			// TODO create new note
		},
		selectCategory(category) {
			this.filter.category = category
		},
		categoryLabel(category) {
			return category === '' ? t('notes', 'Uncategorized') : category
		}
	},
}
</script>
