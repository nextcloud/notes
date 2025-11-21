<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal>
		<div class="editor-hint-modal">
			<h2>{{ t('notes', 'Rich text editor') }}</h2>

			<p>{{ t('notes', 'You can now switch to use the easy to use and distraction free rich text editor. It allows you to edit notes without seeing any Markdown marks.') }}</p>

			<p>{{ t('notes', 'This option can also be changed later on in the Notes app settings.') }}</p>

			<div class="submit-buttons">
				<NcButton type="secondary" :disabled="loading" @click="useOld">
					{{ t('notes', 'Keep plain Markdown editor') }}
				</NcButton>
				<NcButton type="primary" :disabled="loading" @click="useNew">
					{{ t('notes', 'Use rich editor') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>
<script>
import NcModal from '@nextcloud/vue/components/NcModal'
import NcButton from '@nextcloud/vue/components/NcButton'
import { loadState } from '@nextcloud/initial-state'

import { deleteEditorMode, setSettings } from './../../NotesService.js'

export default {
	components: {
		NcModal,
		NcButton,
	},
	data() {
		return {
			loading: false,
		}
	},
	methods: {
		async useOld() {
			const oldState = loadState('notes', 'config', {})
			setSettings({
				...oldState,
				noteMode: oldState?.nodeMode ?? 'edit',
			})
			await deleteEditorMode()
			this.$emit('close')
		},
		async useNew() {
			setSettings({
				...loadState('notes', 'config', {}),
				noteMode: 'rich',
			})
			await deleteEditorMode()
			this.$emit('close')
		},
	},
}
</script>
<style lang="scss" scoped>
.editor-hint-modal {
	margin: 24px;
}

.submit-buttons {
	display: flex;
	justify-content: flex-end;
	margin-top: 24px;

	button {
		margin-inline-start: 12px;
	}
}
</style>
