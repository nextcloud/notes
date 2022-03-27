<template>
	<ul>
		<NoteItem v-for="note in notes"
			:key="note.id"
			:note="note"
			@note-deleted="$emit('note-deleted', $event)"
		/>
	</ul>
</template>

<script>
import NoteItem from './NoteItem'

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
}
</script>
