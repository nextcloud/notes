<template>
	<Fragment class="app-navigation-noclose separator-below">
		<AppNavigationItem
			:title="t('notes', 'All notes')"
			icon="icon-recent"
			:class="{ active: null === selectedCategory }"
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
			:class="{ active: category.name === selectedCategory }"
			@click.prevent.stop="onSelectCategory(category.name)"
		>
			<AppNavigationCounter slot="counter">
				{{ category.count }}
			</AppNavigationCounter>
		</AppNavigationItem>

		<AppNavigationSpacer class="separator-above" />
	</Fragment>
</template>

<script>
import {
	AppNavigationItem,
	AppNavigationCounter,
	AppNavigationSpacer,
} from '@nextcloud/vue'
import { Fragment } from 'vue-fragment'

import { getCategories } from '../NotesService'
import { categoryLabel } from '../Util'

import store from '../store'

export default {
	name: 'NavigationCategoriesList',

	components: {
		Fragment,
		AppNavigationItem,
		AppNavigationCounter,
		AppNavigationSpacer,
	},

	props: {
		selectedCategory: {
			type: String,
			default: null,
		},
	},

	computed: {
		numNotes() {
			return store.getters.numNotes()
		},

		categories() {
			return getCategories(1, true)
		},
	},

	methods: {
		categoryTitle(category) {
			return categoryLabel(category)
		},

		onSelectCategory(category) {
			this.$emit('category-selected', category)
		},
	},
}
</script>
<style scoped>
.separator-above {
	border-top: 1px solid var(--color-border);
}
</style>
