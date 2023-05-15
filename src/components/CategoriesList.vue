<template>
	<Fragment>
		<NcAppNavigationItem
			:title="t('notes', 'All notes')"
			:class="{ active: selectedCategory === null }"
			@click.prevent.stop="onSelectCategory(null)"
		>
			<HistoryIcon slot="icon" :size="20" />
			<NcAppNavigationCounter slot="counter">
				{{ numNotes }}
			</NcAppNavigationCounter>
		</NcAppNavigationItem>

		<NcAppNavigationCaption :title="t('notes', 'Categories')" />

		<NcAppNavigationItem v-for="category in categories"
			:key="category.name"
			:title="categoryTitle(category.name)"
			:icon="category.name === '' ? 'icon-emptyfolder' : 'icon-files'"
			:class="{ active: category.name === selectedCategory }"
			@click.prevent.stop="onSelectCategory(category.name)"
		>
			<FolderOutlineIcon v-if="category.name === ''" slot="icon" :size="20" />
			<FolderIcon v-else slot="icon" :size="20" />
			<NcAppNavigationCounter slot="counter">
				{{ category.count }}
			</NcAppNavigationCounter>
		</NcAppNavigationItem>
	</Fragment>
</template>

<script>
import {
	NcAppNavigationItem,
	NcAppNavigationCaption,
	NcAppNavigationCounter,
} from '@nextcloud/vue'
import { Fragment } from 'vue-fragment'

import FolderIcon from 'vue-material-design-icons/Folder.vue'
import FolderOutlineIcon from 'vue-material-design-icons/FolderOutline.vue'
import HistoryIcon from 'vue-material-design-icons/History.vue'

import { getCategories } from '../NotesService.js'
import { categoryLabel } from '../Util.js'
import store from '../store.js'

export default {
	name: 'CategoriesList',

	components: {
		Fragment,
		NcAppNavigationItem,
		NcAppNavigationCaption,
		NcAppNavigationCounter,
		FolderIcon,
		FolderOutlineIcon,
		HistoryIcon,
	},

	computed: {
		numNotes() {
			return store.getters.numNotes()
		},

		categories() {
			return getCategories(1, true)
		},

		selectedCategory() {
			return store.getters.getSelectedCategory()
		},
	},

	methods: {
		categoryTitle(category) {
			return categoryLabel(category)
		},

		onSelectCategory(category) {
			store.commit('setSelectedCategory', category)
		},
	},
}
</script>
<style scoped>
.app-navigation-entry-wrapper.active:deep(.app-navigation-entry) {
	background-color: var(--color-primary-element-light) !important;
}
</style>
