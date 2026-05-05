<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul>
		<NoteItem v-for="note in notes"
			:key="`${note.id}-${showCategoryTitle ? 'with-category-title' : 'without-category-title'}`"
			:note="note"
			:renaming="isRenaming(note.id)"
			:show-category-title="showCategoryTitle"
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
		showCategoryTitle: {
			type: Boolean,
			default: false,
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
