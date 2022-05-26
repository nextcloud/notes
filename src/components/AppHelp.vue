<template>
	<div>
		<AppSettingsDialog :open.sync="settingsOpen" showNavigation=true>
			<h2>{{ t('notes', 'Notes Application') }}</h2>
			<AppSettingsSection title="Basics">
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
			<AppSettingsSection title="Markdown">
				<div class="feature icon-toggle-filelist">
					{{ t('notes', 'Use Markdown markups to style your text.') }}
				</div>
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
			<AppSettingsSection title="Shortcuts">
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
			<AppSettingsSection title="Tips and Tricks">
			<div class="feature icon-toggle-filelist">
				{{ t('notes', 'Double Click the text in viewmode to quickly open edit mode!') }}
			</div>

		</AppSettingsSection>
			<AppSettingsSection title="Apps">
				<div class="feature icon-phone">
					{{ t('notes', 'Install the app for your mobile phone in order to access your notes from everywhere.') }}
					<br>
					<div >
						{{ t('notes', 'Android app') }}
						<br>
						<a target="_blank" href="https://github.com/stefan-niedermann/nextcloud-notes">
							<img src="https://play.google.com/intl/en_us/badges/images/generic/en_badge_web_generic.png"
								 alt="Get it on Play Store"
								 height="80">
						</a>

						<a target="_blank" href="https://f-droid.org/repository/browse/?fdid=it.niedermann.owncloud.notes">
							<img src="https://f-droid.org/badge/get-it-on.png"
								 alt="Get it on F-Droid"
								 height="80">
						</a>
					</div>

					<div >
						{{ t('notes', 'iOS app') }}
						<br>
						<a target="_blank" href="https://apps.apple.com/app/cloudnotes-owncloud-notes/id813973264">
							<img src="https://developer.apple.com/app-store/marketing/guidelines/images/badge-example-preferred.png"
								 alt="Get it on Apple S"
								 height="80">
						</a>
					</div>


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
import {setSettings} from "../NotesService";


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
		return {
			settingsOpen: true,
		}
	},

	computed: {
		getShortcuts() {
			return [
				{shortcut: "CTRL+'", action: "toggleBlockquote"},
				{shortcut: "CTRL+B", action: "toggleBold"},
				{shortcut: "CTRL+E", action: "cleanBlock"},
				{shortcut: "CTRL+H", action: "toggleHeadingSmaller"},
				{shortcut: "CTRL+I", action: "toggleItalic"},
				{shortcut: "CTRL+K", action: "drawLink"},
				{shortcut: "CTRL+L", action: "toggleUnorderedList"},
				{shortcut: "CTRL+P", action: "togglePreview"},
				{shortcut: "CTRL+Alt+C", action: "toggleCodeBlock"},
				{shortcut: "CTRL+Alt+I", action: "drawImage"},
				{shortcut: "CTRL+Alt+L", action: "toggleOrderedList"},
				{shortcut: "Shift+Cmd+H", action: "toggleHeadingBigger"},
				{shortcut: "F9", action: "toggleSideBySide"},
				{shortcut: "F11", action: "toggleFullScreen"},
				{shortcut: "CTRL+/", action: "Switch between Editor and Viewer"},
			]
		},
		getMarkdown() {
			return [
				{sequence: "**test**", result: "make bold", visualized: "<b>test</b>"},
				{sequence: "*italics*", result: "make italic", visualized: "<em>test</em>"},
				{sequence: "~~strikethrough~~", result: "strikethrough", visualized: "<s>test</s>"},

				{sequence: "# Big header", result: "Big header", visualized: "<h1>test</h1>"},
				{sequence: "## Medium header", result: "Medium header", visualized: "<h2>test</h2>"},
				{sequence: "### Small header", result: "Small header", visualized: "<h3>test</h3>"},
				{sequence: "#### Tiny header", result: "Tiny header", visualized: "<h4>test</h4>"},

				{sequence: "* Generic list item", result: "Generic list item", visualized: "<li>test</li>"},
				{sequence: "1. Numbered list item<br>2. Numbered list item<br>4. Numbered list item<br>", result: "Generic list item", visualized: "<li>1. test</li><li>2. test</li><li>3. test</li>"},


				{sequence: "[Text to display](http://www.example.com)", result: "link", visualized: "<a href='http://www.example.com'>Text to display</a>"},
				{sequence: "![Alt Title](http://www.example.com/image.jpg)", result: "link", visualized: "<img src='http://www.example.com' alt='Alt Title'></img>"},
				{sequence: "> This is a quote.<br>> It can span multiple lines!", result: "Quote", visualized: ""},


				{sequence: "`code`", result: "code", visualized: ""},
				{sequence: "```<br>Multi Line Blockcode<br>```", result: "mlb", visualized: ""},
			]
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
table {
	width: 70%;
}

th {
	font-style: oblique;
}
tr:nth-child(even) {
	background-color: #dddddd;
}
</style>
