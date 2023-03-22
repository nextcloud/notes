<template>
	<NcAppNavigationSettings :title="t('notes', 'Notes settings')" :class="{ loading: saving }" :exclude-click-outside-selectors="['div.oc-dialog']">
		<div class="settings-block">
			<p class="settings-hint">
				<label for="notesPath">{{ t('notes', 'Folder to store your notes') }}</label>
			</p>
			<input id="notesPath"
				v-model="settings.notesPath"
				type="text"
				name="notesPath"
				:placeholder="t('notes', 'Root directory')"
				@click="onChangeNotePath"
			>
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
import { FilePicker, FilePickerType } from '@nextcloud/dialogs'
import store from '../store.js'

/*
* Workaround until excludeClickOutsideSelectors will work and does nott close the menu while
* clicking in the filePicker
*/
const callback = (mutationList, observer) => {
	for (const mutation of mutationList) {
		if (mutation.target.id === 'app-settings__content' && mutation.type === 'attributes' && mutation.attributeName === 'style') {
			mutation.target.style = 'display: block;'
			document.getElementById('app-settings').classList.add('open')
			observer.disconnect()
		}
	}
}

export default {
	name: 'AppSettings',

	components: {
		NcAppNavigationSettings,
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
		}
	},

	computed: {
		settings() {
			return store.state.app.settings
		},
	},

	created() {
		if (!window.OCA.Text?.createEditor) {
			this.noteModes.splice(0, 1)
		}
	},

	methods: {
		onChangeNotePath(event) {
			// Obeserver for the workaround
			const observer = new MutationObserver(callback)
			observer.observe(document.querySelector('#app-settings__content'), { attributes: true })

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
