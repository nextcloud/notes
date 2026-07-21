<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NoteRich v-if="isRichMode" :note-id="noteId" />
	<NotePlain v-else-if="isPlainMode" :note-id="noteId" />
	<div v-else />
</template>

<script>
import NotePlain from './NotePlain.vue'
import NoteRich from './NoteRich.vue'
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
		isRichMode() {
			return OC.appswebroots?.text && store.state.app?.settings?.noteMode === 'rich'
		},

		isPlainMode() {
			return store.state.app?.settings?.noteMode === 'edit' || store.state.app?.settings?.noteMode === 'preview'
		},
	},
}
</script>
