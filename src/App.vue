<template>
	<AppContent app-name="notes">
		<template slot="navigation">
			<AppNavigationNew :text="t('notes', 'New note')"
				buttonId="notes_new_note"
				buttonClass="icon-add"
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
			var categories = this.categories
			var categoryItems = []
			categoryItems.push({
				text: t('notes', 'All notes'),
				icon: 'nav-icon-recent',
				action: this.selectCategory.bind(this, null),
				utils: {
					counter: this.notes.length,
				},
			})
			for (var i = 0; i < categories.length; i++) {
				var category = categories[i]
				var item = {
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
			var items = []
			for (var i = 0; i < this.notes.length; i++) {
				var item = { text: this.notes[i].title }
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
