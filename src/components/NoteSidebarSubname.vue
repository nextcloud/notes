<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<span class="note-sidebar-subname">
		<span>{{ size }}</span>

		<template v-if="node.mtime">
			<span class="note-sidebar-subname__separator">•</span>
			<NcDateTime :timestamp="node.mtime" />
		</template>

		<template v-if="node.owner">
			<span class="note-sidebar-subname__separator">•</span>
			<NcUserBubble class="note-sidebar-subname__owner"
				:displayName="ownerDisplayName"
				:title="t('notes', 'Owner')"
				:user="node.owner"
			/>
		</template>
	</span>
</template>

<script>
import { formatFileSize } from '@nextcloud/files'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'

export default {
	name: 'NoteSidebarSubname',

	components: {
		NcDateTime,
		NcUserBubble,
	},

	props: {
		node: {
			type: Object,
			required: true,
		},
	},

	computed: {
		size() {
			return formatFileSize(this.node.size ?? 0)
		},

		ownerDisplayName() {
			return this.node.attributes?.['owner-display-name']
		},
	},
}
</script>

<style scoped>
.note-sidebar-subname {
	display: inline-flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 0 8px;
}

.note-sidebar-subname__separator {
	display: inline-block;
	font-weight: bold;
}

.note-sidebar-subname__owner {
	display: inline-flex !important;
}
</style>
