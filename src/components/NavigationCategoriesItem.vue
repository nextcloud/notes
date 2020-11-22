<template>
	<AppNavigationItem
		:title="title"
		icon="icon-files"
		class="app-navigation-noclose separator-below"
		:class="{ 'category-header': selectedCategory !== null }"
		:open.sync="open"
		:allow-collapse="true"
		@click.prevent.stop="onToggleCategories"
	>
		<AppNavigationItem
			:title="t('notes', 'All notes')"
			icon="icon-recent"
			@click.prevent.stop="onSelectCategory(null)"
		>
			<AppNavigationCounter slot="counter">
				{{ numNotes }}
			</AppNavigationCounter>
		</AppNavigationItem>

		<AppNavigationItem v-for="category in categories"
			:key="category.name"
			:title="categoryTitle(category.name)"
			:icon="category.name === '' ? 'icon-emptyfolder' : 'icon-files'"
			@click.prevent.stop="onSelectCategory(category.name)"
		>
			<AppNavigationCounter slot="counter">
				{{ category.count }}
			</AppNavigationCounter>
		</AppNavigationItem>
	</AppNavigationItem>
</template>

<script>
import {
	AppNavigationItem,
	AppNavigationCounter,
} from '@nextcloud/vue'

import { getCategories, categoryLabel } from '../NotesService'

import store from '../store'

export default {
	name: 'NavigationCategoriesItem',

	components: {
		AppNavigationItem,
		AppNavigationCounter,
	},

	props: {
		selectedCategory: {
			type: String,
			default: null,
		},
	},

	data() {
		return {
			open: false,
		}
	},

	computed: {
		numNotes() {
			return store.getters.numNotes()
		},

		categories() {
			return getCategories(1, true)
		},

		title() {
			return this.selectedCategory === null ? this.t('notes', 'Categories') : categoryLabel(this.selectedCategory)
		},
	},

	methods: {
		categoryTitle(category) {
			return categoryLabel(category)
		},

		onToggleCategories() {
			this.open = !this.open
		},

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
