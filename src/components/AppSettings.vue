<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSettingsDialog
		:name="t('notes', 'Notes settings')"
		:class="{ loading: saving }"
		:show-navigation="true"
		:open="settingsOpen"
		:legacy="false"
		@update:open="setSettingsOpen($event)"
	>
		<NcAppSettingsSection id="note-mode-section" :name="t('notes', 'General')">
			<NcRadioGroup
				v-model="settings.noteMode"
				:label="t('notes', 'Display')"
				@update:modelValue="onChangeSettings"
			>
				<NcRadioGroupButton
					v-for="mode in noteModes"
					id="noteMode"
					:key="mode.value"
					:label="mode.label"
					:value="mode.value"
				>
					<template #icon>
						<component
							:is="mode.icon"
							:size="20"
						/>
					</template>
				</NcRadioGroupButton>
			</NcRadioGroup>

			<NcRadioGroup
				v-model="settings.fileSuffix"
				:label="t('notes', 'File extension')"
				:description="t('notes', 'For new notes')"
				@update:modelValue="onChangeSettings"
			>
				<NcRadioGroupButton
					v-for="extension in extensions"
					:key="extension.value"
					:label="extension.label"
					:value="extension.value"
				/>
			</NcRadioGroup>
			<NcTextField v-show="settings.fileSuffix === 'custom'"
				id="customSuffix"
				:label="t('notes', 'Custom file extension')"
				placeholder=".txt"
				@change="onChangeSettings"
			/>

			<NcFormGroup :label="t('notes', 'Files')">
				<NcFormBox>
					<NcFormBoxButton :label="t('notes', 'Notes folder')"
						:description=" '/' + settings.notesPath"
						inverted-accent
						@click="onChangeNotePath"
					>
						<template #icon>
							<FolderOpenOutlineIcon :size="20" />
						</template>
					</NcFormBoxButton>
				</NcFormBox>
			</NcFormGroup>
		</NcAppSettingsSection>

		<NcAppSettingsSection :name="t('notes', 'Mobile apps')">
			<HelpMobile />
		</NcAppSettingsSection>

		<NcAppSettingsShortcutsSection :name="t('notes', 'Shortcuts')">
			<NcHotkeyList>
				<NcHotkey v-for="(item, index) in shortcuts"
					:key="index"
					:label="item.action"
					:hotkey="item.shortcut"
				/>
			</NcHotkeyList>
		</NcAppSettingsShortcutsSection>
	</NcAppSettingsDialog>
</template>

<script>
import NcAppSettingsDialog from '@nextcloud/vue/components/NcAppSettingsDialog'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcAppSettingsShortcutsSection from '@nextcloud/vue/components/NcAppSettingsShortcutsSection'
import NcHotkeyList from '@nextcloud/vue/components/NcHotkeyList'
import NcHotkey from '@nextcloud/vue/components/NcHotkey'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import { getFilePickerBuilder } from '@nextcloud/dialogs'

import { setSettings } from '../NotesService.js'
import store from '../store.js'
import HelpMobile from './HelpMobile.vue'
import EyeOutlineIcon from 'vue-material-design-icons/EyeOutline.vue'
import FormatAlignLeftIcon from 'vue-material-design-icons/FormatAlignLeft.vue'
import NewspaperVariantOutlineIcon from 'vue-material-design-icons/NewspaperVariantOutline.vue'
import FolderOpenOutlineIcon from 'vue-material-design-icons/FolderOpenOutline.vue'

export default {
	name: 'AppSettings',

	components: {
		NcTextField,
		NcAppSettingsDialog,
		NcAppSettingsSection,
		HelpMobile,
		NcAppSettingsShortcutsSection,
		NcHotkeyList,
		NcHotkey,
		NcRadioGroup,
		NcRadioGroupButton,
		NcFormBox,
		NcFormBoxButton,
		NcFormGroup,
		EyeOutlineIcon,
		FormatAlignLeftIcon,
		NewspaperVariantOutlineIcon,
		FolderOpenOutlineIcon,
	},

	props: {
		open: Boolean,
	},

	data() {
		return {
			extensions: [
				{ value: '.md', label: '.md' },
				{ value: '.txt', label: '.txt' },
				{ value: 'custom', label: t('notes', 'Custom') },
			],
			noteModes: [
				{ value: 'rich', label: t('notes', 'Rich text'), icon: 'NewspaperVariantOutlineIcon' },
				{ value: 'edit', label: t('notes', 'Plain text'), icon: 'FormatAlignLeftIcon' },
				{ value: 'preview', label: t('notes', 'Preview'), icon: 'EyeOutlineIcon' },
			],
			saving: false,
			settingsOpen: this.open,
			shortcuts: [
				{ shortcut: 'Control B', action: t('notes', 'Make the selection bold') },
				{ shortcut: 'Control I', action: t('notes', 'Make the selection italic') },
				{ shortcut: 'Control +', action: t('notes', 'Wrap the selection in quotes') },
				{ shortcut: 'Control Alt C', action: t('notes', 'The selection will be turned into monospace') },
				{ shortcut: 'Control E', action: t('notes', 'Remove any styles from the selected text') },
				{ shortcut: 'Control L', action: t('notes', 'Makes the current line a list element') },
				{ shortcut: 'Control Alt L', action: t('notes', 'Makes the current line a list element with a number') },
				{ shortcut: 'Control H', action: t('notes', 'Toggle heading for current line') },
				{ shortcut: 'Control Shift H', action: t('notes', 'Set the current line as a big heading') },
				{ shortcut: 'Control K', action: t('notes', 'Insert link') },
				{ shortcut: 'Control Alt I', action: t('notes', 'Insert image') },
				{ shortcut: 'Control /', action: t('notes', 'Switch between editor and viewer') },
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
		async onChangeNotePath(event) {
			const filePicker = getFilePickerBuilder(t('notes', 'Pick a notes folder'))
				.allowDirectories(true)
				.startAt(event.target.value === '' ? '/' : event.target.value)
				.addButton({
					label: t('notes', 'Set notes folder'),
					callback: (nodes) => {
						const node = nodes[0]
						this.settings.notesPath = node.path
						this.onChangeSettingsReload()
					},
				})
				.build()

			await filePicker.pick()

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
