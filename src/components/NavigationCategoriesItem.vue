<template>
	<AppNavigationItem
		:item="mainItem"
		:open.sync="open"
	/>
</template>

<script>
import {
	AppNavigationItem,
} from 'nextcloud-vue'
import NotesService from '../NotesService'
import store from '../store'

export default {
	name: 'NavigationCategoriesItem',

	components: {
		AppNavigationItem,
	},

	props: {
		selectedCategory: {
			type: String,
			default: null,
		},
	},

	data: function() {
		return {
			open: false,
		}
	},

	computed: {
		numNotes() {
			return store.getters.numNotes()
		},

		categories() {
			return NotesService.getCategories(1, true)
		},

		categoryItems() {
			const itemAllNotes = {
				text: this.t('notes', 'All notes'),
				icon: 'nav-icon-recent',
				action: this.onSelectCategory.bind(this, null),
				utils: {
					counter: this.numNotes,
				},
			}
			const itemsCategories = this.categories.map(category => (
				{
					text: NotesService.categoryLabel(category.name),
					icon: category.name === '' ? 'nav-icon-emptyfolder' : 'nav-icon-files',
					action: this.onSelectCategory.bind(this, category.name),
					utils: {
						counter: category.count,
					},
				}
			))
			return [ itemAllNotes, ...itemsCategories ]
		},

		mainItem() {
			return {
				text: this.selectedCategory === null ? this.t('notes', 'Categories') : NotesService.categoryLabel(this.selectedCategory),
				icon: 'nav-icon-files',
				collapsible: true,
				classes: 'app-navigation-noclose separator-below' + (this.selectedCategory === null ? '' : ' category-header'),
				children: this.categoryItems,
			}
		},
	},

	methods: {
		onSelectCategory(category) {
			this.open = false
			this.$emit('category-selected', category)
		},
	},
}
</script>
<style scoped>
.separator-below {
	border-bottom: 1px solid var(--color-border);
}
</style>
