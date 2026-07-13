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
		isRichMode() {
			return OC.appswebroots?.text && store.app?.settings?.noteMode === 'rich'
		},
		isPlainMode() {
			return store.app?.settings?.noteMode === 'edit' || store.app?.settings?.noteMode === 'preview'
		},
	},
}
</script>
