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
		<NavigationCategoriesList
			:selected-category="selectedCategory"
			@category-selected="onSelectCategory"
		/>
	</AppNavigationItem>
</template>

<script>
import {
	AppNavigationItem,
} from '@nextcloud/vue'

import { categoryLabel } from '../Util'
import NavigationCategoriesList from './NavigationCategoriesList.vue'

export default {
	name: 'NavigationCategoriesItem',

	components: {
		AppNavigationItem,
		NavigationCategoriesList,
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
