<template>
	<AppContent>
		<div class="welcome-content">
			<h2>{{ t('notes', 'Notes') }}</h2>
			<div class="feature icon-add">
				{{ t('notes', 'Start writing a note by clicking on “{newnote}” in the app navigation.', { newnote: t('notes', 'New note') }) }}
			</div>
			<div class="feature icon-fullscreen">
				{{ t('notes', 'Write down your thoughts without any distractions.') }}
			</div>
			<div class="feature icon-toggle-filelist">
				{{ t('notes', 'Use Markdown markups to style your text.') }}
			</div>
			<div class="feature icon-files-dark">
				{{ t('notes', 'Organize your notes in categories (use “{label}” action to edit a note\'s category).', { label: t('notes', 'Details') }) }}
			</div>
			<div class="feature icon-phone">
				{{ t('notes', 'Install the app for your mobile phone in order to access your notes from everywhere.') }}
				<ul>
					<li><a target="_blank" href="https://github.com/stefan-niedermann/nextcloud-notes">{{ t('notes', 'Android app') }}</a></li>
					<li><a target="_blank" href="https://github.com/owncloud/notes-iOS-App">{{ t('notes', 'iOS app') }}</a></li>
				</ul>
			</div>
		</div>
	</AppContent>
</template>
<script>

import {
	AppContent,
} from '@nextcloud/vue'

export default {
	name: 'Welcome',

	components: {
		AppContent,
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

.feature {
	background-position: left top;
	min-height: 32px;
	padding-left: 32px;
	margin-top: 1em;
}

.feature ul {
	list-style: circle outside;
	padding-left: 2em;
}
</style>
