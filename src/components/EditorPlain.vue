<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<p class="editor">
		{{ value }}
	</p>
</template>
<script>

export default {
	name: 'EditorPlain',

	props: {
		value: {
			type: String,
			required: true,
		},
	},
}
</script>
<style scoped>
.editor {
	padding: 1em;
	font-size: 150%;
	white-space: pre-wrap;
}
</style>
