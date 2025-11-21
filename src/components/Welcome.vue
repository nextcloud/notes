<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent>
		<div class="welcome-content">
			<h2>{{ t('notes', 'Notes') }}</h2>
			<div class="feature icon-add">
				{{ t('notes', 'Start writing a note by clicking on “{newnote}” in the app navigation.', { newnote: t('notes', 'New note') }) }}
			</div>
			<div class="feature">
				<NcButton type="secondary" @click="onNewNote">
					<Plus slot="icon" :size="20" />
					{{ t('notes', 'New note') }}
				</NcButton>
			</div>
			<div class="feature icon-fullscreen">
				{{ t('notes', 'Write down your thoughts without any distractions.') }}
			</div>
			<div class="feature icon-toggle-filelist">
				{{ t('notes', 'Use Markdown markups to style your text.') }}
			</div>
			<div class="feature">
				<CreateSampleButton />
			</div>
			<div class="feature icon-files-dark">
				{{ t('notes', 'Organize your notes in categories.') }}
			</div>
			<HelpMobile />
		</div>
	</NcAppContent>
</template>
<script>

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'

import Plus from 'vue-material-design-icons/Plus.vue'

import CreateSampleButton from './CreateSampleButton.vue'
import HelpMobile from './HelpMobile.vue'

import { createNote } from '../NotesService.js'

export default {
	name: 'Welcome',

	components: {
		CreateSampleButton,
		HelpMobile,
		NcAppContent,
		NcButton,
		Plus,
	},

	methods: {
		onNewNote() {
			createNote()
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
<style scoped>
.welcome-content {
	padding: 4em 8em;
}

@media (max-width: 60em) {
	.welcome-content {
		margin: auto;
		max-width: 40em;
		padding: 2em 2em 1em 1em;
	}
}

.welcome-content h2 {
	margin-bottom: 1em;
}

.welcome-content a {
	color: var(--color-primary-element);
}

</style>
