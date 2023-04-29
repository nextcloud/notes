<template>
	<NoteRich v-if="ready && rich" :note-id="noteId" />
	<NotePlain v-else-if="ready" :note-id="noteId" />
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
			return window.oc_appswebroots.text && store.state.app.settings.noteMode === 'rich'
		},
		ready() {
			return store.state.app.settings.noteMode !== null
		},
	},
}
</script>
