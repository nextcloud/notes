<template>
	<app-navigation :menu="menu">
		<template slot="settings-content">
			Example settings
		</template>
	</app-navigation>
</template>

<script>
import { AppNavigation } from 'nextcloud-vue'
import store from './store'

export default {
	name: 'AppNotesNavigation',
	components: {
		AppNavigation
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
		noteItems() {
			var items = []
			for (var i = 0; i < this.notes.length; i++) {
				var item = { text: this.notes[i].title }
				items.push(item)
			}
			return items
		},
		menu() {
			var items = []

			var categoryItem = {
				text: this.filter.category === null ? t('notes', 'Categories') : this.categoryLabel(this.filter.category),
				icon: 'nav-icon-files',
				collapsible: true,
				classes: 'app-navigation-noclose separator-below' + (this.filter.category === null ? '' : ' category-header'),
				children: this.categoryItems,
			}
			items.push(categoryItem)

			items.push.apply(items, this.noteItems)

			return {
				new: {
					id: 'new-note-button',
					text: t('notes', 'New note'),
					icon: 'icon-add',
					action: this.newNote,
				},
				items: items,
				loading: false
			}
		},
	},
	methods: {
		newNote() {
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
