<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul class="notes-list">
		<NoteItem v-for="note in notes"
			:key="`${note.id}-${showCategoryTitle ? 'with-category-title' : 'without-category-title'}`"
			:note="note"
			:renaming="isRenaming(note.id)"
			:showCategoryTitle="showCategoryTitle"
			@noteSelected="onNoteSelected"
			@startRenaming="onStartRenaming"
			@noteDeleted="onNoteDeleted"
		/>
	</ul>
</template>

<script>
import NoteItem from './NoteItem.vue'

export default {
	name: 'NotesList',

	components: {
		NoteItem,
	},

	props: {
		notes: {
			type: Array,
			required: true,
		},

		showCategoryTitle: {
			type: Boolean,
			default: false,
		},
	},

	emits: [
		'noteSelected',
		'noteDeleted',
	],

	data() {
		return {
			renamingNotes: [],
		}
	},

	methods: {
		onNoteSelected(noteId) {
			this.$emit('noteSelected', noteId)
		},

		onStartRenaming(noteId) {
			this.renamingNotes.push(noteId)
		},

		onNoteDeleted(note) {
			this.$emit('noteDeleted', note)
		},

		isRenaming(noteId) {
			return this.renamingNotes.includes(noteId)
		},

	},
}
</script>

<style lang="scss" scoped>
.notes-list:deep(.list-item__wrapper) {
	box-sizing: border-box;
	padding-block: 0;
}

.notes-list:deep(.list-item__wrapper:first-of-type) {
	padding-block-start: 0;
}

.notes-list:deep(.list-item__wrapper:last-of-type) {
	padding-block-end: 0;
}
</style>
