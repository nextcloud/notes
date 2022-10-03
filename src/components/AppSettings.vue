<template>
	<NcAppNavigationSettings :title="t('notes', 'Notes settings')" :class="{ loading: saving }">
		<div class="settings-block">
			<p class="settings-hint">
				<label for="notesPath">{{ t('notes', 'Folder to store your notes') }}</label>
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
		</div>
		<div class="settings-block">
			<p class="settings-hint">
				<label for="fileSuffix">{{ t('notes', 'File extension for new notes') }}</label>
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
		</div>
		<div class="settings-block">
			<p class="settings-hint">
				<label for="noteMode">{{ t('notes', 'Display mode for notes') }}</label>
			</p>
			<select id="noteMode" v-model="settings.noteMode" @change="onChangeSettings">
				<option v-for="mode in noteModes" :key="mode.value" :value="mode.value">
					{{ mode.label }}
				</option>
			</select>
		</div>
	</NcAppNavigationSettings>
</template>

<script>
import {
	NcAppNavigationSettings,
} from '@nextcloud/vue'

import { setSettings } from '../NotesService.js'
import store from '../store.js'

export default {
	name: 'AppSettings',

	components: {
		NcAppNavigationSettings,
	},

	data() {
		return {
			extensions: [
				{ value: '.txt', label: '.txt' },
				{ value: '.md', label: '.md' },
				{ value: 'custom', label: t('notes', 'User defined') },
			],
			noteModes: [
				{ value: 'edit', label: t('notes', 'Open in edit mode') },
				{ value: 'preview', label: t('notes', 'Open in preview mode') },
			],
			saving: false,
		}
	},

	computed: {
		settings() {
			return store.state.app.settings
		},
	},

	created() {
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
