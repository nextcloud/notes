<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcButton type="secondary" @click="onNewNote">
		<Plus slot="icon" :size="20" />
		{{ t('notes', 'Create a sample note with Markdown') }}
	</NcButton>
</template>
<script>

import NcButton from '@nextcloud/vue/components/NcButton'

import Plus from 'vue-material-design-icons/Plus.vue'

import { createNote } from '../NotesService.js'
import { getDefaultSampleNote, getDefaultSampleNoteTitle } from '../Util.js'

export default {
	components: {
		NcButton,
		Plus,
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
