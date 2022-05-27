<template>
	<NcAppContent>
		<div class="welcome-content">
			<h2>{{ t('notes', 'Notes') }}</h2>
			<div class="feature icon-add">
				{{ t('notes', 'Start writing a note by clicking on “{newnote}” in the app navigation.', { newnote: t('notes', 'New note') }) }}
			</div>
			<button @click="onNewNote">{{ t('notes', 'Create a Sample note with markdown') }}</button>
			<div class="feature icon-fullscreen">
				{{ t('notes', 'Write down your thoughts without any distractions.') }}
			</div>
			<div class="feature icon-toggle-filelist">
				{{ t('notes', 'Use Markdown markups to style your text.') }}
			</div>
			<div class="feature icon-files-dark">
				{{ t('notes', 'Organize your notes in categories.') }}
			</div>
			<div class="feature icon-phone">
				{{ t('notes', 'Install the app for your mobile phone in order to access your notes from everywhere.') }}
				<ul>
					<li><a target="_blank" href="https://github.com/stefan-niedermann/nextcloud-notes">{{ t('notes', 'Android app') }}</a></li>
					<li><a target="_blank" href="https://github.com/owncloud/notes-iOS-App">{{ t('notes', 'iOS app') }}</a></li>
				</ul>
			</div>
		</div>
	</NcAppContent>
</template>
<script>

import {
	NcAppContent,
} from '@nextcloud/vue'
import { createNote } from '../NotesService'
import { getDefaultSampleNote } from '../Util'

export default {
	name: 'Welcome',

	components: {
		NcAppContent,
	},

	methods: {
		onNewNote() {
			createNote('')
				.then(note => {
					const query = { new: getDefaultSampleNote() }
					this.$router.push({
						name: 'note',
						params: { noteId: note.id.toString() },
						query
					})
				})
				.catch(() => {
				})
		},
	},
}

</script>
<style>
.welcome-content {
	padding: 2em 3em;
}

.welcome-content h2 {
	margin-bottom: 1em;
}

.welcome-content a {
	color: var(--color-primary-element);
}

.feature {
	background-position: left center;
	width: 100%;
	min-height: 32px;
	line-height: 32px;
	padding-left: 32px;
	margin-top: 0.3em !important;
	margin-bottom: 0.3em !important;
	display: inline-block;
	vertical-align: middle;
}

.feature ul {
	list-style: circle outside;
	padding-left: 2em;
}
</style>
