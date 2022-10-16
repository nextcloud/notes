<template>
	<NcAppContent>
		<div class="welcome-content">
			<h2>{{ t('notes', 'Notes') }}</h2>
			<NcButton type="secondary" @click="onNewNote">
				<PlusIcon slot="icon" :size="20" />
				{{ t('notes', 'Create a sample note with markdown') }}
			</NcButton>
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
				{{ t('notes', 'Organize your notes in categories.') }}
			</div>
			<div class="feature icon-phone">
				{{ t('notes', 'Install the app for your mobile phone in order to access your notes from everywhere.') }}
			</div>
			<div class="badge-wrapper">
				<a target="_blank" href="https://github.com/stefan-niedermann/nextcloud-notes">
					{{ t('notes', 'Android app: {notes} by {company}', {notes: 'Nextcloud Notes', company: 'Niedermann IT-Dienstleistungen'}) }}
				</a>
				<div>
					<div class="badge">
						<a target="_blank" href="https://play.google.com/store/apps/details?id=it.niedermann.owncloud.notes">
							<img :src="getRoute('badge_playstore.svg')" class="appstore-badge badge-playstore-fix">
						</a>
					</div>
					<div class="badge">
						<a target="_blank" href="https://f-droid.org/repository/browse/?fdid=it.niedermann.owncloud.notes">
							<img :src="getRoute('badge_fdroid.svg')" class="appstore-badge">
						</a>
					</div>
				</div>
			</div>
			<div class="badge-wrapper">
				<a target="_blank" href="https://github.com/phedlund/CloudNotes">
					{{ t('notes', 'iOS app: {notes} by {company}', {notes: 'CloudNotes - Nextcloud Notes', company: 'Peter Hedlund'}) }}
				</a>
				<div>
					<div class="badge">
						<a target="_blank" href="https://apps.apple.com/app/cloudnotes-owncloud-notes/id813973264">
							<img :src="getRoute('badge_applestore.svg')" class="appstore-badge badge-playstore-fix">
						</a>
					</div>
				</div>
			</div>
		</div>
	</NcAppContent>
</template>
<script>

import {
	NcAppContent,
	NcButton,
} from '@nextcloud/vue'
import { createNote } from '../NotesService.js'
import { getDefaultSampleNote } from '../Util.js'
import { generateFilePath } from '@nextcloud/router'
import PlusIcon from 'vue-material-design-icons/Plus.vue'

export default {
	name: 'Welcome',

	components: {
		NcAppContent,
		NcButton,
		PlusIcon,
	},

	methods: {
		onNewNote() {
			createNote('')
				.then(note => {
					const query = { new: getDefaultSampleNote() }
					this.$router.push({
						name: 'note',
						params: { noteId: note.id.toString() },
						query,
					})
				})
				.catch(() => {
				})
		},
		getRoute(file) {
			return generateFilePath('notes', 'img', file)
		},
	},
}

</script>
<style>
.welcome-content {
	padding: 4em 8em;
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

.badge-wrapper {
	margin-top: 2em;
	margin-left: 2em;
	width: 100%;
	clear:both;
}

.badge {
	float:left; /* add this */
}

.appstore-badge {
	height: 72px;
}

.badge-playstore-fix {
	padding: 12px;
}

.button-icon-add {
	background-position: 10px center;
	padding-left: 34px !important;
}

</style>
