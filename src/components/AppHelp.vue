<template>
	<div>
		<NcAppSettingsDialog
			:title="t('notes', 'Help')"
			:show-navigation="true"
			:open.sync="helpOpen"
			@update:open="emitOpen($event)"
		>
			<NcAppSettingsSection id="help-basics" :title="t('notes', 'Basics')">
				<div class="feature icon-add">
					{{ t('notes', 'Start writing a note by clicking on “{newnote}” in the app navigation.', { newnote: t('notes', 'New note') }) }}
				</div>
				<div class="feature icon-fullscreen">
					{{ t('notes', 'Write down your thoughts without any distractions.') }}
				</div>
				<div class="feature icon-files-dark">
					{{ t('notes', 'Organize your notes in categories.') }}
				</div>
				<NcButton type="secondary" @click="onNewNote">
					<PlusIcon slot="icon" :size="20" />
					{{ t('notes', 'Create a sample note with markdown') }}
				</NcButton>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="help-markdown" :title="t('notes', 'Markdown')">
				<div class="feature icon-toggle-filelist">
					{{ t('notes', 'Use Markdown markups to style your text.') }}
				</div>
				<table>
					<tr>
						<th>
							{{ t('notes', 'Sequence') }}
						</th>
						<th>
							{{ t('notes', 'Result') }}
						</th>
						<th>
							{{ t('notes', 'Visualized') }}
						</th>
					</tr>
					<tr v-for="(item, index) in getMarkdown" :key="index">
						<!-- eslint-disable-next-line vue/no-v-html -->
						<td v-html="item.sequence" />
						<td>
							{{ item.result }}
						</td>
						<!-- eslint-disable-next-line vue/no-v-html -->
						<td v-html="item.visualized" />
					</tr>
				</table>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="help-shortcuts" :title="t('notes', 'Shortcuts')">
				<div class="feature icon-toggle-filelist">
					{{ t('notes', 'Use shortcuts to quickly navigate this app.') }}
				</div>
				<table>
					<tr>
						<th>{{ t('notes', 'Shortcut') }}</th>
						<th>{{ t('notes', 'Action') }}</th>
					</tr>
					<tr v-for="(item, index) in getShortcuts" :key="index">
						<td>{{ item.shortcut }}</td>
						<td>{{ item.action }}</td>
					</tr>
				</table>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="help-apps" :title="t('notes', 'Mobile apps')">
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
			</NcAppSettingsSection>
		</NcAppSettingsDialog>
	</div>
</template>

<script>

import {
	NcAppSettingsDialog,
	NcAppSettingsSection,
	NcButton,
} from '@nextcloud/vue'
import { createNote } from '../NotesService.js'
import { getDefaultSampleNote } from '../Util.js'
import { generateFilePath } from '@nextcloud/router'
import PlusIcon from 'vue-material-design-icons/Plus.vue'

export default {
	name: 'AppHelp',

	components: {
		NcAppSettingsDialog,
		NcAppSettingsSection,
		NcButton,
		PlusIcon,
	},

	props: {
		open: Boolean,
	},

	data() {
		return {
			helpOpen: this.open,
		}
	},

	computed: {
		getShortcuts() {
			return [
				{ shortcut: t('notes', 'CTRL+\''), action: t('notes', 'Wrap the selection in Quotes') },
				{ shortcut: t('notes', 'CTRL+B'), action: t('notes', 'Make the selection bold') },
				{ shortcut: t('notes', 'CTRL+E'), action: t('notes', 'Remove any styles from the selected text') },
				{ shortcut: t('notes', 'CTRL+H'), action: t('notes', 'Toggle heading for current line') },
				{ shortcut: t('notes', 'CTRL+ALT+C'), action: t('notes', 'The selection will be turned into monospace') },
				{ shortcut: t('notes', 'CTRL+ALT+I'), action: t('notes', 'Insert image at the cursor') },
				{ shortcut: t('notes', 'CTRL+ALT+L'), action: t('notes', 'Makes the current line a list element with a number') },
				{ shortcut: t('notes', 'SHIFT+CTRL+H'), action: t('notes', 'Set the current line as a big Heading') },
				{ shortcut: t('notes', 'CTRL+I'), action: t('notes', 'Make the selection italic') },
				{ shortcut: t('notes', 'CTRL+K'), action: t('notes', 'Insert link at cursor') },
				{ shortcut: t('notes', 'CTRL+L'), action: t('notes', 'Makes the current line a list element') },
				{ shortcut: t('notes', 'F11'), action: t('notes', 'Make the note fullscreen') },
				{ shortcut: t('notes', 'CTRL+/'), action: t('notes', 'Switch between Editor and Viewer') },
			]
		},
		getMarkdown() {
			return [
				{ sequence: '**' + t('notes', 'bolding') + '**', result: 'Bolding', visualized: '<b>' + t('notes', 'bolding') + '</b>' },
				{ sequence: '*' + t('notes', 'italic') + '*', result: 'Italicising', visualized: '<em>' + t('notes', 'italic') + '</em>' },
				{ sequence: '~~' + t('notes', 'strikethrough') + '~~', result: 'strikethrough', visualized: '<s>' + t('notes', 'strikethrough') + '</s>' },

				{ sequence: '# ' + t('notes', 'Big header'), result: t('notes', 'Big header'), visualized: '<h2>' + t('notes', 'Big header') + '</h2>' },
				{ sequence: '## ' + t('notes', 'Medium header'), result: t('notes', 'Medium header'), visualized: '<b><h3>' + t('notes', 'Medium header') + '</h3></b>' },
				{ sequence: '### ' + t('notes', 'Small header'), result: t('notes', 'Small header'), visualized: '<h3>' + t('notes', 'Small header') + '</h3>' },
				{ sequence: '#### ' + t('notes', 'Tiny header'), result: t('notes', 'Tiny header'), visualized: '<h4>' + t('notes', 'Tiny header') + '</h4>' },

				{ sequence: '* ' + t('notes', 'Generic list item'), result: t('notes', 'Generic list'), visualized: '<li>' + t('notes', 'Generic list item') + '</li>' },
				{ sequence: '- ' + t('notes', 'Generic list item'), result: t('notes', 'Generic list'), visualized: '<li>' + t('notes', 'Generic list item') + '</li>' },

				{
					sequence: '1. William Riker<br>2. Deanna Troi<br>3. Beverly Crusher<br>',
					result: t('notes', 'Numbered list'),
					visualized: '<ol><li>William Riker</li><li>Deanna Troi</li><li>Beverly Crusher</li></ol>',
				},

				{ sequence: '[Text to display](http://www.example.com)', result: "'link'", visualized: "<a href='http://www.example.com'>Text to display</a>" },
				{ sequence: '![Alt Title](http://www.example.com/image.jpg)', result: 'link', visualized: "<img src='http://www.example.com' alt='Alt Title'></img>" },
				{ sequence: '> This is a quote.<br>> It can span multiple lines!', result: 'Quote', visualized: '' },

				{ sequence: '`code`', result: 'code', visualized: '' },
				{ sequence: '```<br>Multi Line Blockcode<br>```', result: 'mlb', visualized: '' },
			]
		},
	},

	watch: {
		open(newValue) {
			this.helpOpen = newValue
		},
	},

	methods: {
		emitOpen(newValue) {
			this.$emit('update:open', this.helpOpen)
		},

		onNewNote() {
			this.helpOpen = false
			this.emitOpen(this.helpOpen)
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

<style scoped>

.exit {
	width: 15px;
	float: right;
}

table {
	width: 70%;
	border: 1px lightgray solid;
	border-spacing: 0;

	border-collapse:separate;
	border-radius:6px;
}

th {
	font-style: oblique;
	font-weight: bold;
	border-top: none;
}

th , td {
	padding: 5px;
	border-left: 1px lightgray solid;
}

td:first-child, th:first-child {
	border-left: none;
}

tr:nth-child(even) {
	background-color: #eeeeee;
}

.badge-playstore-fix {
	height: 48px;
	padding: 12px;
}

</style>
