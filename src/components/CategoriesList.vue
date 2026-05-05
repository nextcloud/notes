<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<NcAppNavigationItem
			:name="t('notes', 'All notes')"
			:draggable="false"
			:class="{
				active: selectedCategory === null,
				'drop-over': dragOverAllNotes,
				'category-no-actions': true,
			}"
			@click.prevent.stop="onSelectCategory(null)"
			@dragstart.native="onCategoryDragStart"
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

		<NcAppNavigationItem
			v-if="newCategoryDraft"
			ref="newCategoryItem"
			:name="''"
			:draggable="false"
			:icon="'icon-files'"
			:editable="true"
			:edit-label="t('notes', 'Rename category')"
			:edit-placeholder="t('notes', 'New category')"
			:force-menu="true"
			:force-display-actions="true"
			menu-icon="icon-more"
			class="category-draft"
			@click.prevent.stop
			@dragstart.native="onCategoryDragStart"
			@update:name="onCreateCategory"
		>
			<template #icon>
				<FolderIcon :size="20" />
			</template>
			<template #counter>
				<NcCounterBubble>
					0
				</NcCounterBubble>
			</template>
		</NcAppNavigationItem>

		<NcAppNavigationItem v-for="category in categories"
			:key="category.name"
			:name="categoryTitle(category.name)"
			:draggable="false"
			:icon="category.name === '' ? 'icon-emptyfolder' : 'icon-files'"
			:editable="category.name !== ''"
			:edit-label="t('notes', 'Rename category')"
			:edit-placeholder="category.name"
			:force-menu="category.name !== ''"
			:force-display-actions="category.name !== ''"
			menu-icon="icon-more"
			:class="{
				active: category.name === selectedCategory,
				'drop-over': category.name === dragOverCategory,
				'category-no-actions': category.name === '',
			}"
			@click.prevent.stop="onSelectCategory(category.name)"
			@dragstart.native="onCategoryDragStart"
			@dragover.native="onCategoryDragOver(category.name, $event)"
			@dragleave.native="onCategoryDragLeave(category.name, $event)"
			@drop.native="onCategoryDrop(category.name, $event)"
			@update:name="onRenameCategory(category.name, $event)"
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
			<template v-if="category.name !== ''" #actions>
				<NcActionButton
					icon="icon-delete"
					:close-after-click="true"
					@click="onDeleteCategory(category.name)"
				>
					{{ t('notes', 'Delete category') }}
				</NcActionButton>
			</template>
		</NcAppNavigationItem>
	</Fragment>
</template>

<script>
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import { Fragment } from 'vue-frag'
import { showConfirmation } from '@nextcloud/dialogs'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import FolderIcon from 'vue-material-design-icons/Folder.vue'
import FolderOutlineIcon from 'vue-material-design-icons/FolderOutline.vue'
import HistoryIcon from 'vue-material-design-icons/History.vue'

import { deleteCategory as deleteCategoryRequest, getCategories, renameCategory as renameCategoryRequest, setCategory } from '../NotesService.js'
import { categoryLabel, getDraggedNoteId, isNoteDrag } from '../Util.js'
import store from '../store.js'

export default {
	name: 'CategoriesList',

	components: {
		Fragment,
		NcActionButton,
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
			newCategoryDraft: false,
			newCategoryMonitor: null,
			newCategoryDropNoteId: null,
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

	mounted() {
		subscribe('notes:category:new', this.startNewCategory)
	},

	destroyed() {
		unsubscribe('notes:category:new', this.startNewCategory)
		this.stopNewCategoryMonitor()
	},

	methods: {
		categoryTitle(category) {
			return categoryLabel(category)
		},

		startNewCategory(payload = {}) {
			if (this.newCategoryDraft) {
				return
			}
			this.newCategoryDropNoteId = payload?.noteId ?? null
			this.newCategoryDraft = true
			this.$nextTick(() => {
				this.$refs.newCategoryItem?.handleEdit?.()
				this.monitorNewCategoryEditing()
			})
		},

		stopNewCategoryMonitor() {
			if (this.newCategoryMonitor) {
				cancelAnimationFrame(this.newCategoryMonitor)
				this.newCategoryMonitor = null
			}
		},

		monitorNewCategoryEditing() {
			this.stopNewCategoryMonitor()
			const check = () => {
				if (!this.newCategoryDraft) {
					this.stopNewCategoryMonitor()
					this.newCategoryDropNoteId = null
					return
				}
				const item = this.$refs.newCategoryItem
				if (item && item.editingActive === false) {
					this.newCategoryDraft = false
					this.stopNewCategoryMonitor()
					this.newCategoryDropNoteId = null
					return
				}
				this.newCategoryMonitor = requestAnimationFrame(check)
			}
			this.newCategoryMonitor = requestAnimationFrame(check)
		},

		onCreateCategory(newCategory) {
			const trimmed = newCategory?.trim() ?? ''
			this.newCategoryDraft = false
			this.stopNewCategoryMonitor()
			const droppedNoteId = this.newCategoryDropNoteId
			this.newCategoryDropNoteId = null
			if (!trimmed) {
				return
			}
			const exists = this.categories.some(category => category.name === trimmed)
			if (!exists) {
				store.commit('addLocalCategory', trimmed)
			}
			store.commit('setSelectedCategory', trimmed)
			if (droppedNoteId !== null) {
				setCategory(droppedNoteId, trimmed).catch(() => {})
			}
		},

		getNotesInCategory(category) {
			return store.state.notes.notes.filter(note => note.category === category || note.category.startsWith(category + '/'))
		},

		updateNotesForCategoryRename(oldCategory, newCategory) {
			for (const note of store.state.notes.notes) {
				if (note.category === oldCategory || note.category.startsWith(oldCategory + '/')) {
					const updatedCategory = note.category.startsWith(oldCategory + '/')
						? newCategory + note.category.slice(oldCategory.length)
						: newCategory
					store.commit('setNoteAttribute', { noteId: note.id, attribute: 'category', value: updatedCategory })
				}
			}
		},

		removeNotesFromCategory(categoryName) {
			for (const note of this.getNotesInCategory(categoryName)) {
				store.commit('removeNote', note.id)
			}
		},

		updateSelectedCategoryForRename(oldCategory, newCategory) {
			const selected = this.selectedCategory
			if (selected === oldCategory) {
				store.commit('setSelectedCategory', newCategory)
				return
			}
			if (selected && selected.startsWith(oldCategory + '/')) {
				store.commit('setSelectedCategory', newCategory + selected.slice(oldCategory.length))
			}
		},

		clearSelectedCategoryForDelete(category) {
			const selected = this.selectedCategory
			if (selected === category || (selected && selected.startsWith(category + '/'))) {
				store.commit('setSelectedCategory', null)
			}
		},

		async onRenameCategory(category, newCategory) {
			const trimmed = newCategory?.trim() ?? ''
			if (!trimmed || trimmed === category) {
				return
			}

			try {
				const response = await renameCategoryRequest(category, trimmed)
				const oldName = category
				const newName = response?.newCategory || trimmed
				this.updateNotesForCategoryRename(oldName, newName)
				store.commit('renameLocalCategory', { oldCategory: oldName, newCategory: newName })
				this.updateSelectedCategoryForRename(oldName, newName)
			} catch {
				// NotesService already shows a toast on failure.
			}
		},

		async onDeleteCategory(categoryName) {
			const notes = this.getNotesInCategory(categoryName)
			if (notes.length > 0) {
				let confirmed = false
				const message = this.n(
					'notes',
					'Delete category "{category}" and its {count} note?',
					'Delete category "{category}" and its {count} notes?',
					notes.length,
					{ category: this.categoryTitle(categoryName), count: notes.length },
				)
				try {
					confirmed = await showConfirmation({
						name: this.t('notes', 'Delete category'),
						text: message,
						labelConfirm: this.t('notes', 'Delete'),
						labelReject: this.t('notes', 'Cancel'),
						severity: 'warning',
					})
				} catch {
					confirmed = window.confirm(message)
				}
				if (!confirmed) {
					return
				}
			}

			try {
				await deleteCategoryRequest(categoryName)
				const deletedCategory = categoryName
				await this.closeOpenNoteBeforeDelete(deletedCategory)
				this.removeNotesFromCategory(deletedCategory)
				store.commit('removeLocalCategory', deletedCategory)
				this.clearSelectedCategoryForDelete(deletedCategory)
			} catch {
				// NotesService already shows a toast on failure.
			}
		},

		onCategoryDragOver(category, event) {
			if (!isNoteDrag(event)) {
				this.dragOverCategory = null
				this.dragOverAllNotes = false
				return
			}
			event.preventDefault()
			if (event.dataTransfer) {
				event.dataTransfer.dropEffect = 'move'
			}
			this.dragOverAllNotes = false
			this.dragOverCategory = category
		},

		onAllNotesDragOver(event) {
			if (!isNoteDrag(event)) {
				this.dragOverCategory = null
				this.dragOverAllNotes = false
				return
			}
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
			const noteId = getDraggedNoteId(event, noteId => store.getters.getNote(noteId))
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

			const noteId = getDraggedNoteId(event, noteId => store.getters.getNote(noteId))
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

		async closeOpenNoteBeforeDelete(categoryName) {
			const noteId = Number.parseInt(this.$route?.params?.noteId, 10)
			if (!Number.isFinite(noteId)) {
				return
			}
			const note = store.getters.getNote(noteId)
			if (!note) {
				return
			}
			if (note.category === categoryName || note.category.startsWith(categoryName + '/')) {
				const remainingNote = store.state.notes.notes.find(other => (
					other.id !== noteId
					&& other.category !== categoryName
					&& !other.category.startsWith(categoryName + '/')
				))
				if (remainingNote) {
					await this.$router.push({
						name: 'note',
						params: { noteId: remainingNote.id.toString() },
					}).catch(() => {})
				} else {
					await this.$router.push({ name: 'welcome' }).catch(() => {})
				}
			}
		},

		onCategoryDragStart(event) {
			event.preventDefault()
			event.stopPropagation()
		},
	},
}
</script>
<style lang="scss" scoped>
.app-navigation-entry-wrapper.active:deep(.app-navigation-entry) {
	background-color: var(--color-primary-element) !important;
}

.app-navigation-entry-wrapper.active:deep(.app-navigation-entry:hover),
.app-navigation-entry-wrapper.active:deep(.app-navigation-entry:focus-within) {
	background-color: var(--color-primary-element-hover) !important;
}

.app-navigation-entry-wrapper.drop-over:deep(.app-navigation-entry) {
	background-color: var(--color-primary-element) !important;
	outline: 2px dashed var(--color-primary-element-text);
	outline-offset: -2px;
}

.app-navigation-entry-wrapper.active:deep(.app-navigation-entry-link),
.app-navigation-entry-wrapper.active:deep(.app-navigation-entry-button),
.app-navigation-entry-wrapper.active:deep(.material-design-icon),
.app-navigation-entry-wrapper.drop-over:deep(.app-navigation-entry-link),
.app-navigation-entry-wrapper.drop-over:deep(.app-navigation-entry-button),
.app-navigation-entry-wrapper.drop-over:deep(.material-design-icon) {
	color: var(--color-primary-element-text) !important;
}

.app-navigation-entry-wrapper.category-no-actions:deep(.app-navigation-entry__counter-wrapper) {
	margin-inline-end: calc(var(--default-grid-baseline) * 2 + var(--default-clickable-area));
}
</style>
