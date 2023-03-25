<template>
	<NcAppSettingsDialog
		:title="t('notes', 'Notes settings')"
		:class="{ loading: saving }"
		:show-navigation="true"
		:open="settingsOpen"
		@update:open="setSettingsOpen($event)"
	>
		<NcAppSettingsSection id="notes-path-section" :title="t('notes', 'Notes path')">
			<p class="app-settings-section__desc">
				{{ t('notes', 'Folder to store your notes') }}
			</p>
			<form @submit.prevent="onChangeSettingsReload">
				<input id="notesPath"
					v-model="settings.notesPath"
					type="text"
					name="notesPath"
					:placeholder="t('notes', 'Root directory')"
					@change="onChangeSettingsReload"
				><input type="submit" class="icon-confirm" value="">
			</form>
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
	</NcAppSettingsDialog>
</template>

<script>
import {
	NcAppSettingsDialog,
	NcAppSettingsSection,
} from '@nextcloud/vue'

import { setSettings } from '../NotesService.js'
import store from '../store.js'

export default {
	name: 'AppSettings',

	components: {
		NcAppSettingsDialog,
		NcAppSettingsSection,
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
