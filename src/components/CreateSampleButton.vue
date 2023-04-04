<template>
	<NcButton type="secondary" @click="onNewNote">
		<PlusIcon slot="icon" :size="20" />
		{{ t('notes', 'Create a sample note with Markdown') }}
	</NcButton>
</template>
<script>

import {
	NcButton,
} from '@nextcloud/vue'

import PlusIcon from 'vue-material-design-icons/Plus.vue'

import { createNote } from '../NotesService.js'
import { getDefaultSampleNote, getDefaultSampleNoteTitle } from '../Util.js'

export default {
	components: {
		NcButton,
		PlusIcon,
	},

	methods: {
		onNewNote() {
			this.$emit('click')
			createNote('', getDefaultSampleNoteTitle(), getDefaultSampleNote())
				.then(note => {
					this.$router.push({
						name: 'note',
						params: { noteId: note.id.toString() },
					})
				})
		},
	},
}

</script>
