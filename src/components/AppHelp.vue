<template>
	<div>
		<NcAppSettingsDialog
			:title="t('notes', 'Help')"
			:show-navigation="true"
			:open="helpOpen"
			@update:open="setHelpOpen($event)"
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
			</NcAppSettingsSection>
			<NcAppSettingsSection id="help-markdown" :title="t('notes', 'Markdown')">
				<div class="feature icon-toggle-filelist">
					{{ t('notes', 'Use Markdown markups to style your text.') }}
				</div>
				<p>
					<CreateSampleButton @click="setHelpOpen(false)" />
				</p>
				<br>
				<table class="notes-help">
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
				<table class="notes-help">
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
				<HelpMobile />
			</NcAppSettingsSection>
		</NcAppSettingsDialog>
	</div>
</template>

<script>

import {
	NcAppSettingsDialog,
	NcAppSettingsSection,
} from '@nextcloud/vue'

import CreateSampleButton from './CreateSampleButton.vue'
import HelpMobile from './HelpMobile.vue'

export default {
	name: 'AppHelp',

	components: {
		CreateSampleButton,
		HelpMobile,
		NcAppSettingsDialog,
		NcAppSettingsSection,
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
				{ shortcut: t('notes', 'CTRL') + '+\'', action: t('notes', 'Wrap the selection in Quotes') },
				{ shortcut: t('notes', 'CTRL') + '+B', action: t('notes', 'Make the selection bold') },
				{ shortcut: t('notes', 'CTRL') + '+E', action: t('notes', 'Remove any styles from the selected text') },
				{ shortcut: t('notes', 'CTRL') + '+H', action: t('notes', 'Toggle heading for current line') },
				{ shortcut: t('notes', 'CTRL') + '+' + t('notes', 'ALT') + '+C', action: t('notes', 'The selection will be turned into monospace') },
				{ shortcut: t('notes', 'CTRL') + '+' + t('notes', 'ALT') + '+I', action: t('notes', 'Insert image at the cursor') },
				{ shortcut: t('notes', 'CTRL') + '+' + t('notes', 'ALT') + '+L', action: t('notes', 'Makes the current line a list element with a number') },
				{ shortcut: t('notes', 'CTRL') + '+' + t('notes', 'SHIFT') + '+H', action: t('notes', 'Set the current line as a big Heading') },
				{ shortcut: t('notes', 'CTRL') + '+I', action: t('notes', 'Make the selection italic') },
				{ shortcut: t('notes', 'CTRL') + '+K', action: t('notes', 'Insert link at cursor') },
				{ shortcut: t('notes', 'CTRL') + '+L', action: t('notes', 'Makes the current line a list element') },
				{ shortcut: 'F11', action: t('notes', 'Make the note fullscreen') },
				{ shortcut: t('notes', 'CTRL') + '+/', action: t('notes', 'Switch between Editor and Viewer') },
			]
		},
		getMarkdown() {
			return [
				{ sequence: '**' + t('notes', 'bolding') + '**', result: t('notes', 'bolding'), visualized: '<b>' + t('notes', 'bolding') + '</b>' },
				{ sequence: '*' + t('notes', 'italic') + '*', result: t('notes', 'italic'), visualized: '<em>' + t('notes', 'italic') + '</em>' },
				{ sequence: '~~' + t('notes', 'strikethrough') + '~~', result: t('notes', 'strikethrough'), visualized: '<s>' + t('notes', 'strikethrough') + '</s>' },

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

				{ sequence: '[' + t('notes', 'Link title') + '](http://www.example.com)', result: t('notes', 'link'), visualized: '<a href="http://www.example.com">' + t('notes', 'Link title') + '</a>' },
				{ sequence: '![' + t('notes', 'Image title') + '](http://www.example.com/image.jpg)', result: t('notes', 'image'), visualized: '<img src="http://www.example.com" alt="' + t('notes', 'Image title') + '" />' },
				{ sequence: '> ' + t('notes', 'This is a quote.'), result: t('notes', 'quote'), visualized: '<blockquote>' + t('notes', 'This is a quote.') + '</blockquote>' },

				{ sequence: '`' + t('notes', 'code') + '`', result: t('notes', 'code'), visualized: '<code>' + t('notes', 'code') + '</code>' },
				{ sequence: '```<br>' + t('notes', 'Multi line block code') + '<br>```', result: t('notes', 'Multi line block code'), visualized: '<pre>' + t('notes', 'Multi line block code') + '</pre>' },
			]
		},
	},

	watch: {
		open(newValue) {
			this.helpOpen = newValue
		},
	},

	methods: {
		setHelpOpen(newValue) {
			this.helpOpen = newValue
			this.$emit('update:open', newValue)
		},
	},
}
</script>

<style scoped>

table.notes-help {
	width: 70%;
	border: 1px lightgray solid;
	border-spacing: 0;

	border-collapse:separate;
	border-radius:6px;
}

table.notes-help th {
	font-style: oblique;
	font-weight: bold;
	border-top: none;
}

table.notes-help th, table.notes-help td {
	padding: 5px;
	border-left: 1px lightgray solid;
}

table.notes-help td:first-child, table.notes-help th:first-child {
	border-left: none;
}

table.notes-help tr:nth-child(even) {
	background-color: #eeeeee;
}

</style>
