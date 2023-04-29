<template>
	<h3 class="title">
		{{ title }}
	</h3>
</template>

<script>
export default {
	name: 'NotesCaption',

	components: {
	},

	props: {
		title: {
			type: String,
			required: true,
		},
	},
}
</script>

<style scoped>
	.title {
		font-weight: bold;
		color: var(--color-primary-element);
		font-size: var(--default-font-size);
		line-height: 44px;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		opacity: .7;
		box-shadow: none !important;
		flex-shrink: 0;
		padding: 0 calc(var(--default-grid-baseline, 4px) * 2) 0 calc(var(--default-grid-baseline, 4px) * 3);
	}
</style>
