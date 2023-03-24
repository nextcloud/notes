<template>
	<Fragment>

	<NcAppNavigationItem
		:title="title"
		class="app-navigation-noclose separator-below"
		:class="{ 'category-header': selectedCategory !== null }"
		:open.sync="open"
		:menu-open.sync="menuOpen"
		:allow-collapse="true"
		@click.prevent.stop="onToggleCategories"
	>
		<template #menu-icon>
			<AddIcon :size="20" @click="onToggleNewCategory" />
		</template>
		<template #actions>
			<NcActionText>
				<template #icon>
					<ErrorIcon v-if="createCategoryError" :size="20" />
					<AddIcon v-else-if="!createCategoryError" :size="20" />
				</template>
				{{ createCategoryError ? createCategoryError : t('notes', 'Create a new category') }}
			</NcActionText>
			<NcActionInput
				icon=""
				:value="t('notes', 'Category name')"
				@submit.prevent.stop="createNewCategory"
			>
				<template #icon>
					<FolderIcon :size="20" />
				</template>
			</NcActionInput>
		</template>

		<FolderIcon slot="icon" :size="20" />
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
	NcActionInput,
	NcActionText,
	NcAppNavigationItem,
	NcAppNavigationCaption,
	NcAppNavigationCounter,
} from '@nextcloud/vue'
import { Fragment } from 'vue-fragment'

import FolderIcon from 'vue-material-design-icons/Folder.vue'
import FolderOutlineIcon from 'vue-material-design-icons/FolderOutline.vue'
import HistoryIcon from 'vue-material-design-icons/History.vue'
import AddIcon from 'vue-material-design-icons/Plus.vue'
import ErrorIcon from 'vue-material-design-icons/AlertCircle.vue'

import { createCategory, findCategory, getCategories } from '../NotesService.js'
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
		AddIcon,
		ErrorIcon,
		NcActionInput,
		NcActionText,
	},

	data() {
		return {
			open: false,
			menuOpen: false,
			createCategoryError: null,
		}
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

		onToggleCategories() {
			this.open = !this.open
		},

		onToggleNewCategory() {
			this.menuOpen = !this.menuOpen
		},

		onSelectCategory(category) {
			store.commit('setSelectedCategory', category)
		},

		createNewCategory(event) {
			const input = event.target.querySelector('input[type=text]')
			const categoryName = input.value.trim()

			// Check if already exists
			findCategory(categoryName).then(data => {
				if (data !== false) {
					this.createCategoryError = t('notes', 'This category already exists')
					return
				}

				// Create new directory for category in current notes path defined in settings
				createCategory(categoryName)
				this.createCategoryError = null
				this.onToggleNewCategory()
				this.onSelectCategory(categoryName)
			})

		},
	},
}
</script>
<style scoped>
.app-navigation-entry-wrapper.active:deep(.app-navigation-entry) {
	background-color: var(--color-primary-element-light) !important;
}
</style>
