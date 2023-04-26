<template>
	<NcAppSettingsDialog
		:title="t('notes', 'Notes settings')"
		:class="{ loading: saving }"
		:show-navigation="true"
		:open="settingsOpen"
		@update:open="setSettingsOpen($event)"
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
		<NcAppSettingsSection id="notes-path-section" :title="t('notes', 'Notes path')">
			<p class="app-settings-section__desc">
				{{ t('notes', 'Folder to store your notes') }}
			</p>
			<input id="notesPath"
				v-model="settings.notesPath"
				type="text"
				name="notesPath"
				:placeholder="t('notes', 'Root directory')"
				@click="onChangeNotePath"
			>
		</NcAppSettingsSection>
		<NcAppSettingsSection id="file-suffix-section" :title="t('notes', 'File extension')">
			<p class="app-settings-section__desc">
				{{ t('notes', 'File extension for new notes') }}
			</p>
			<select id="fileSuffix" v-model="settings.fileSuffix" @change="onChangeSettings">
				<option v-for="extension in extensions" :key="extension.value" :value="extension.value">
					{{ extension.label }}
				</option>
			</select>
			<input v-show="settings.fileSuffix === 'custom'"
				id="customSuffix"
				v-model="settings.customSuffix"
				name="customSuffix"
				type="text"
				placeholder=".txt"
				@change="onChangeSettings"
			>
		</NcAppSettingsSection>
		<NcAppSettingsSection id="note-mode-section" :title="t('notes', 'Display mode')">
			<p class="app-settings-section__desc">
				{{ t('notes', 'Display mode for notes') }}
			</p>
			<select id="noteMode" v-model="settings.noteMode" @change="onChangeSettings">
				<option v-for="mode in noteModes" :key="mode.value" :value="mode.value">
					{{ mode.label }}
				</option>
			</select>
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
				<tr v-for="(item, index) in shortcuts" :key="index">
					<td>{{ item.shortcut }}</td>
					<td>{{ item.action }}</td>
				</tr>
			</table>
		</NcAppSettingsSection>
		<NcAppSettingsSection id="help-apps" :title="t('notes', 'Mobile apps')">
			<HelpMobile />
		</NcAppSettingsSection>
	</NcAppSettingsDialog>
</template>

<script>
import {
	NcAppSettingsDialog,
	NcAppSettingsSection,
} from '@nextcloud/vue'

import { FilePicker, FilePickerType } from '@nextcloud/dialogs'

import { setSettings } from '../NotesService.js'
import store from '../store.js'
import HelpMobile from './HelpMobile.vue'

export default {
	name: 'AppSettings',

	components: {
		NcAppSettingsDialog,
		NcAppSettingsSection,
		HelpMobile,
	},

	props: {
		open: Boolean,
	},

	data() {
		return {
			extensions: [
				{ value: '.md', label: '.md' },
				{ value: '.txt', label: '.txt' },
				{ value: 'custom', label: t('notes', 'User defined') },
			],
			noteModes: [
				{ value: 'rich', label: t('notes', 'Open in rich text mode') },
				{ value: 'edit', label: t('notes', 'Open in edit mode') },
				{ value: 'preview', label: t('notes', 'Open in preview mode') },
			],
			saving: false,
			settingsOpen: this.open,
			shortcuts: [
				{ shortcut: t('notes', 'CTRL') + '+B', action: t('notes', 'Make the selection bold') },
				{ shortcut: t('notes', 'CTRL') + '+I', action: t('notes', 'Make the selection italic') },
				{ shortcut: t('notes', 'CTRL') + '+\'', action: t('notes', 'Wrap the selection in quotes') },
				{ shortcut: t('notes', 'CTRL') + '+' + t('notes', 'ALT') + '+C', action: t('notes', 'The selection will be turned into monospace') },
				{ shortcut: t('notes', 'CTRL') + '+E', action: t('notes', 'Remove any styles from the selected text') },
				{ shortcut: t('notes', 'CTRL') + '+L', action: t('notes', 'Makes the current line a list element') },
				{ shortcut: t('notes', 'CTRL') + '+' + t('notes', 'ALT') + '+L', action: t('notes', 'Makes the current line a list element with a number') },
				{ shortcut: t('notes', 'CTRL') + '+H', action: t('notes', 'Toggle heading for current line') },
				{ shortcut: t('notes', 'CTRL') + '+' + t('notes', 'SHIFT') + '+H', action: t('notes', 'Set the current line as a big heading') },
				{ shortcut: t('notes', 'CTRL') + '+K', action: t('notes', 'Insert link') },
				{ shortcut: t('notes', 'CTRL') + '+' + t('notes', 'ALT') + '+I', action: t('notes', 'Insert image') },
				{ shortcut: t('notes', 'CTRL') + '+/', action: t('notes', 'Switch between editor and viewer') },
			],
		}
	},

	computed: {
		settings() {
			return store.state.app.settings
		},
	},

	watch: {
		open(newValue) {
			this.settingsOpen = newValue
		},
	},

	created() {
		if (!window.OCA.Text?.createEditor) {
			this.noteModes.splice(0, 1)
		}
	},

	methods: {
		onChangeNotePath(event) {
			// Code Example from: https://github.com/nextcloud/text/blob/main/src/components/Menu/ActionInsertLink.vue#L130-L155
			const filePicker = new FilePicker(
				t('text', 'Select folder to link to'),
				false, // multiselect
				['text/directory'], // mime filter
				true, // modal
				FilePickerType.Choose, // type
				true, // directories
				event.target.value === '' ? '/' : event.target.value // path
			)
			filePicker.pick().then((file) => {
				const client = OC.Files.getClient()
				client.getFileInfo(file).then((_status, fileInfo) => {
					this.settings.notesPath = fileInfo.path === '/' ? `/${fileInfo.name}` : `${fileInfo.path}/${fileInfo.name}`
					this.onChangeSettingsReload()
				})
			})
		},
		onChangeSettings() {
			this.saving = true
			return setSettings(this.settings)
				.catch(() => {
				})
				.then(() => {
					this.saving = false
				})
		},

		onChangeSettingsReload() {
			this.onChangeSettings()
				.then(() => {
					this.$emit('reload')
				})
		},

		setSettingsOpen(newValue) {
			this.settingsOpen = newValue
			this.$emit('update:open', newValue)
		},
	},
}
</script>
<style scoped>
.loading .settings-block {
	visibility: hidden;
}

.settings-block + .settings-block {
	padding-top: 2ex;
}

.settings-block form {
	display: inline-flex;
}
</style>
