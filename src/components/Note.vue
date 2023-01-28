<template>
	<NoteRich v-if="rich" :note-id="noteId" />
	<NotePlain v-else :note-id="noteId" />
</template>
<script>
import NoteRich from './NoteRich.vue'
import NotePlain from './NotePlain.vue'
import store from '../store.js'

export default {
	name: 'Note',

	components: {
		NoteRich,
		NotePlain,
	},

	props: {
		noteId: {
			type: String,
			required: true,
		},
	},

	computed: {
		rich() {
			return store.state.app.settings.noteMode === 'rich'
		},
	},
}
</script>
