<template>
	<div>
		<AppSettingsDialog :open.sync="settingsOpen" :showNavigation=true>
			<div class="exit icon-close" @click="settingsOpen = false;"></div>
			<h2>{{ t('notes', 'Notes Application') }}</h2>
			<AppSettingsSection :title="t('notes', 'Basics')">
				<div class="feature icon-add">
					{{ t('notes', 'Start writing a note by clicking on “{newnote}” in the app navigation.', { newnote: t('notes', 'New note') }) }}
				</div>
				<div class="feature icon-fullscreen">
					{{ t('notes', 'Write down your thoughts without any distractions.') }}
				</div>
				<div class="feature icon-files-dark">
					{{ t('notes', 'Organize your notes in categories.') }}
				</div>
			</AppSettingsSection>
			<AppSettingsSection :title="t('notes', 'Markdown')">
				<div class="feature icon-toggle-filelist">
					{{ t('notes', 'Use Markdown markups to style your text.') }}
				</div>
				<button @click="onNewNote">{{ t('notes', 'Create a new Note with markdown samples') }}</button>
				<table>
					<tr>
						<th>{{ t('notes', 'Sequence') }}</th>
						<th>{{ t('notes', 'Result') }}</th>
						<th>{{ t('notes', 'Visualized') }}</th>
					</tr>
					<tr v-for="item in getMarkdown">
						<td v-html="item.sequence">{{ item.sequence }}</td>
						<td>{{ item.result }}</td>
						<td v-html="item.visualized">{{ item.visualized }}</td>
					</tr>
				</table>
			</AppSettingsSection>
			<AppSettingsSection :title="t('notes', 'Shortcuts')">
				<div class="feature icon-toggle-filelist">
					{{ t('notes', 'Use shortcuts to quickly navigate this app.') }}
				</div>
				<table>
					<tr>
						<th>{{ t('notes', 'Shortcut') }}</th>
						<th>{{ t('notes', 'Action') }}</th>
					</tr>
					<tr v-for="item in getShortcuts">
						<td>{{ item.shortcut }}</td>
						<td>{{ item.action }}</td>
					</tr>
				</table>
			</AppSettingsSection>
			<AppSettingsSection :title="t('notes', 'Tips and Tricks')">
			<div class="feature icon-toggle-filelist">
				{{ t('notes', 'Double Click the text in viewmode to quickly open edit mode!') }}
			</div>

		</AppSettingsSection>
			<AppSettingsSection :title="t('notes', 'Apps')">
				<div class="feature icon-phone">
					{{ t('notes', 'Install the app for your mobile phone in order to access your notes from everywhere.') }}
					<br>
					<div >
						{{ t('notes', 'Android app') }}
						<br>
						<a target="_blank" href="https://github.com/stefan-niedermann/nextcloud-notes">
							<span class="badge-fdroid"></span>
						</a>

						<a target="_blank" href="https://f-droid.org/repository/browse/?fdid=it.niedermann.owncloud.notes">
							<span class="badge-fdroid"></span>
						</a>
					</div>

					<div >
						{{ t('notes', 'iOS app') }}
						<br>
						<a target="_blank" href="https://apps.apple.com/app/cloudnotes-owncloud-notes/id813973264">
							<span class="badge-fdroid"></span>
						</a>
					</div>
					<span class="badge-fdroid"></span>
					<span class="icon-undo"></span>

				</div>
			</AppSettingsSection>
		</AppSettingsDialog>
	</div>
</template>

<script>


import {
	AppSettingsDialog,
	AppSettingsSection,
} from '@nextcloud/vue'
import { createNote } from '../NotesService';
import { getDefaultSampleNote } from '../Util'

export default {
	name: 'AppHelp',

	props: {
		settingsOpen: Boolean
	},

	components: {
		AppSettingsDialog,
		AppSettingsSection,
	},

	data() {
		return {}
	},

	computed: {
		getShortcuts() {
			return [
				{ shortcut: t('notes', 'CTRL+\''), action: t('notes', 'Make the selection a quote') },
				{ shortcut: t('notes', 'CTRL+B'), action: t('notes', 'Make the selection bold') },
				{ shortcut: t('notes', 'CTRL+E'), action: t('notes', 'cleanBlock') },
				{ shortcut: t('notes', 'CTRL+H'), action: t('notes', 'toggleHeadingSmaller') },
				{ shortcut: t('notes', 'CTRL+I'), action: t('notes', 'Make the selection italic') },
				{ shortcut: t('notes', 'CTRL+K'), action: t('notes', 'Insert link at cursor') },
				{ shortcut: t('notes', 'CTRL+L'), action: t('notes', 'Makes the current line a list element') },
				{ shortcut: t('notes', 'CTRL+P'), action: t('notes', 'Toggle between preview') },
				{ shortcut: t('notes', 'CTRL+Alt+C'), action: t('notes', 'The selection will be turned into monospace') },
				{ shortcut: t('notes', 'CTRL+Alt+I'), action: t('notes', 'Insert image at the cursor') },
				{ shortcut: t('notes', 'CTRL+Alt+L'), action: t('notes', 'kes the current line a list element with a number') },
				{ shortcut: t('notes', 'SHIFT+CTRL+H'), action: t('notes', 'toggleHeadingBigger') },
				{ shortcut: t('notes', 'F9'), action: t('notes', 'toggleSideBySide') },
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
				{ sequence: '1. William Riker<br>2. Deanna Troi<br>3. Beverly Crusher<br>',
					result: t('notes', 'Numbered list'), visualized: '<ol><li>William Riker</li><li>Deanna Troi</li><li>Beverly Crusher</li></ol>' },

				{ sequence: '[Text to display](http://www.example.com)', result: "'link'", visualized: "<a href='http://www.example.com'>Text to display</a>" },
				{ sequence: '![Alt Title](http://www.example.com/image.jpg)', result: 'link', visualized: "<img src='http://www.example.com' alt='Alt Title'></img>" },
				{ sequence: '> This is a quote.<br>> It can span multiple lines!', result: 'Quote', visualized: '' },

				{ sequence: '`code`', result: 'code', visualized: '' },
				{ sequence: '```<br>Multi Line Blockcode<br>```', result: 'mlb', visualized: '' },
			]
		},
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
			this.settingsOpen = false
		},
	},

	watch: {
		settingsOpen: {
			handler: function() {
				this.$emit('popupClosedpopupClosed')
			},
			deep: true
		}
	}
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
</style>
