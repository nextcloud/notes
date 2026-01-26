<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<NcAppNavigationItem
			:name="t('notes', 'All notes')"
			:class="{
				active: selectedCategory === null,
				'drop-over': dragOverAllNotes,
			}"
			@click.prevent.stop="onSelectCategory(null)"
			@dragover.native="onAllNotesDragOver($event)"
			@dragleave.native="onAllNotesDragLeave($event)"
			@drop.native="onAllNotesDrop($event)"
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
			:class="{
				active: category.name === selectedCategory,
				'drop-over': category.name === dragOverCategory,
			}"
			@click.prevent.stop="onSelectCategory(category.name)"
			@dragover.native="onCategoryDragOver(category.name, $event)"
			@dragleave.native="onCategoryDragLeave(category.name, $event)"
			@drop.native="onCategoryDrop(category.name, $event)"
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

import { getCategories, setCategory } from '../NotesService.js'
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

	data() {
		return {
			dragOverCategory: null,
			dragOverAllNotes: false,
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

		getDraggedNoteId(event) {
			const dt = event?.dataTransfer
			if (!dt) {
				return null
			}

			let raw = ''
			try {
				raw = dt.getData('application/x-nextcloud-notes-note-id')
			} catch {
				// Some browsers only allow specific mime types.
			}
			if (!raw) {
				raw = dt.getData('text/plain') || dt.getData('text/uri-list') || dt.getData('text/x-moz-url') || ''
			}

			const match = /\/note\/(\d+)(?:[/?#\s]|$)/.exec(raw) || /^\s*(\d+)\s*$/.exec(raw)
			const noteId = match ? Number.parseInt(match[1], 10) : Number.NaN
			if (!Number.isFinite(noteId)) {
				return null
			}
			const note = store.getters.getNote(noteId)
			if (!note || note.readonly) {
				return null
			}

			return noteId
		},

		onCategoryDragOver(category, event) {
			event.preventDefault()
			if (event.dataTransfer) {
				event.dataTransfer.dropEffect = 'move'
			}
			this.dragOverAllNotes = false
			this.dragOverCategory = category
		},

		onAllNotesDragOver(event) {
			event.preventDefault()
			if (event.dataTransfer) {
				event.dataTransfer.dropEffect = 'move'
			}
			this.dragOverCategory = null
			this.dragOverAllNotes = true
		},

		onAllNotesDragLeave(event) {
			if (!this.dragOverAllNotes) {
				return
			}

			const currentTarget = event.currentTarget
			const relatedTarget = event.relatedTarget
			if (currentTarget && relatedTarget && currentTarget.contains(relatedTarget)) {
				return
			}

			this.dragOverAllNotes = false
		},

		onCategoryDragLeave(category, event) {
			if (this.dragOverCategory !== category) {
				return
			}

			const currentTarget = event.currentTarget
			const relatedTarget = event.relatedTarget
			if (currentTarget && relatedTarget && currentTarget.contains(relatedTarget)) {
				return
			}

			this.dragOverCategory = null
		},

		async onAllNotesDrop(event) {
			event.preventDefault()
			event.stopPropagation()

			this.dragOverAllNotes = false
			const noteId = this.getDraggedNoteId(event)
			if (noteId === null) {
				return
			}

			const note = store.getters.getNote(noteId)
			if (!note || note.category === '') {
				return
			}

			await setCategory(noteId, '')
		},

		async onCategoryDrop(category, event) {
			event.preventDefault()
			event.stopPropagation()

			const noteId = this.getDraggedNoteId(event)
			this.dragOverCategory = null
			this.dragOverAllNotes = false
			if (noteId === null) {
				return
			}

			const note = store.getters.getNote(noteId)
			if (!note || note.category === category) {
				return
			}

			await setCategory(noteId, category)
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

.app-navigation-entry-wrapper.drop-over:deep(.app-navigation-entry) {
	background-color: var(--color-primary-element-light) !important;
	outline: 2px dashed var(--color-primary-element);
	outline-offset: -2px;
}
</style>
