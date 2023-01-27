<template>
	<ul>
		<NoteItem v-for="note in notes"
			:key="note.id"
			:note="note"
			@note-selected="onNoteSelected"
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

	methods: {
		onNoteSelected(noteId) {
			this.$emit('note-selected', noteId)
		},
	},
}
</script>
