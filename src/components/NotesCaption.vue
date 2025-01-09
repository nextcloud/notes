<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<h3 class="name">
		{{ name }}
	</h3>
</template>

<script>
export default {
	name: 'NotesCaption',

	components: {
	},

	props: {
		name: {
			type: String,
			required: true,
		},
	},
}
</script>

<style scoped>
	.name {
		font-weight: bold;
		font-size: var(--default-font-size);
		line-height: var(--default-clickable-area);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		opacity: .7;
		box-shadow: none !important;
		flex-shrink: 0;
		padding: 0 calc(var(--default-grid-baseline, 4px) * 2) 0 calc(var(--default-grid-baseline, 4px) * 3);
	}
</style>
