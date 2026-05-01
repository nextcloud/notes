<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul class="notes-list">
		<NoteItem v-for="note in notes"
			:key="note.id"
			:note="note"
			:renaming="isRenaming(note.id)"
			@note-selected="onNoteSelected"
			@start-renaming="onStartRenaming"
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
	},
	data() {
		return {
			renamingNotes: [],
		}
	},
	methods: {
		onNoteSelected(noteId) {
			this.$emit('note-selected', noteId)
		},
		onStartRenaming(noteId) {
			this.renamingNotes.push(noteId)
		},
		isRenaming(noteId) {
			return this.renamingNotes.includes(noteId)
		},

	},
}
</script>

<style lang="scss" scoped>
.notes-list:deep(.list-item__wrapper) {
	border-block-end: 1px solid var(--color-border);
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
