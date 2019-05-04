<template>
	<AppContent>
		<div class="welcome-content">
			<h2>{{ tn('Notes') }}</h2>
			<div class="feature icon-add">
				{{ tn('Start writing a note by clicking on “{newnote}” in the app navigation.', { newnote: tn('New note') }) }}
			</div>
			<div class="feature icon-fullscreen">
				{{ tn('Write down your thoughts without any distractions.') }}
			</div>
			<div class="feature icon-toggle-filelist">
				{{ tn('Use Markdown markups to style your text.') }}
			</div>
			<div class="feature icon-files-dark">
				{{ tn('Organize your notes in categories.') }}
			</div>
			<div class="feature icon-phone">
				{{ tn('Install the app for your mobile phone in order to access your notes from everywhere.') }}
				<ul>
					<li><a target="_blank" href="https://github.com/stefan-niedermann/nextcloud-notes">{{ tn('Android app') }}</a></li>
					<li><a target="_blank" href="https://github.com/owncloud/notes-iOS-App">{{ tn('iOS app') }}</a></li>
				</ul>
			</div>
		</div>
	</AppContent>
</template>
<script>

import {
	AppContent,
} from 'nextcloud-vue'

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
