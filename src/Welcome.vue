<template>
	<AppContent>
		<div class="welcome-content">
			<h2>{{ tn('Notes') }}</h2>
			<p class="feature icon-add">
				{{ tn('Start writing a note by clicking on “{newnote}” in the app navigation.', { newnote: tn('New note') }) }}
			</p>
			<p class="feature icon-fullscreen">
				{{ tn('Write down your thoughts without any distractions.') }}
			</p>
			<p class="feature icon-toggle-filelist">
				{{ tn('Use Markdown markups to style your text.') }}
			</p>
			<p class="feature icon-tag">
				{{ tn('Organize your notes in categories.') }}
			</p>
			<p class="feature icon-phone">
				{{ tn('Install the Android app or iOS app in order to access your notes from everywhere.') }}
			</p>
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
</style>
