<template>
	<AppNavigationSettings :title="tn('Settings')" :class="{ loading: saving }">
		<div class="settings-block">
			<p class="settings-hint">
				<label for="notesPath">{{ tn('Folder to store your notes') }}</label>
			</p>
			<form @submit.prevent="onChangeSettingsReload">
				<input id="notesPath" v-model="settings.notesPath" type="text"
					name="notesPath" :placeholder="tn('path to notes')"
					@change="onChangeSettingsReload"
				><input type="submit" class="icon-confirm" value="">
			</form>
		</div>
		<div class="settings-block">
			<p class="settings-hint">
				<label for="fileSuffix">{{ tn('File extension for new notes') }}</label>
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
} from 'nextcloud-vue'
import NotesService from '../NotesService'
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
			return store.state.settings
		},
	},

	created() {
	},

	methods: {
		onChangeSettings() {
			this.saving = true
			return NotesService.setSettings(this.settings)
				.catch(() => {
				})
				.finally(() => {
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
