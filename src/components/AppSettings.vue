<template>
	<AppNavigationSettings :title="t('notes', 'Settings')" :class="{ loading: saving }">
		<div class="settings-block">
			<p class="settings-hint">
				<label for="notesPath">{{ t('notes', 'Folder to store your notes') }}</label>
			</p>
			<form @submit.prevent="onChangeSettingsReload">
				<input id="notesPath"
					v-model="settings.notesPath"
					type="text"
					name="notesPath"
					:placeholder="t('notes', 'path to notes')"
					@change="onChangeSettingsReload"
				><input type="submit" class="icon-confirm" value="">
			</form>
		</div>
		<div class="settings-block">
			<p class="settings-hint">
				<label for="fileSuffix">{{ t('notes', 'File extension for new notes') }}</label>
			</p>
			<select id="fileSuffix" v-model="settings.fileSuffix" @change="onChangeSettings">
				<option v-for="ext in extensions" :key="ext" :value="ext">
					{{ ext }}
				</option>
			</select>
		</div>
	</AppNavigationSettings>
</template>

<script>
import {
	AppNavigationSettings,
} from '@nextcloud/vue'

import { setSettings } from '../NotesService'
import store from '../store'

export default {
	name: 'AppSettings',

	components: {
		AppNavigationSettings,
	},

	data: function() {
		return {
			extensions: ['.txt', '.md'],
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
