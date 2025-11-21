<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<NcAppNavigationItem
			:name="t('notes', 'All notes')"
			:class="{ active: selectedCategory === null }"
			@click.prevent.stop="onSelectCategory(null)"
		>
			<template #icon>
				<HistoryIcon :size="20" />
			</template>
			<template #counter>
				<NcCounterBubble>
					{{ numNotes }}
				</NcCounterBubble>
			</template>
		</NcAppNavigationItem>

		<NcAppNavigationCaption :name="t('notes', 'Categories')" />

		<NcAppNavigationItem v-for="category in categories"
			:key="category.name"
			:name="categoryTitle(category.name)"
			:icon="category.name === '' ? 'icon-emptyfolder' : 'icon-files'"
			:class="{ active: category.name === selectedCategory }"
			@click.prevent.stop="onSelectCategory(category.name)"
		>
			<template #icon>
				<FolderOutlineIcon v-if="category.name === ''" :size="20" />
				<FolderIcon v-else :size="20" />
			</template>
			<template #counter>
				<NcCounterBubble>
					{{ category.count }}
				</NcCounterBubble>
			</template>
		</NcAppNavigationItem>
	</Fragment>
</template>

<script>
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import { Fragment } from 'vue-frag'

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
		NcCounterBubble,
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
<style lang="scss" scoped>
.app-navigation-entry-wrapper.active:deep(.app-navigation-entry) {
	background-color: var(--color-primary-element-light) !important;
}
</style>
