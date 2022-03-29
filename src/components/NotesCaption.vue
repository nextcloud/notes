<template>
	<div class="title">
		{{ title }}
	</div>
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
		color: var(--color-primary-light-text);
		font-weight: bold;
		font-size: 1.2em;
		padding-left: 10px;
		text-overflow: ellipsis;
		white-space: nowrap;
		overflow: hidden;
	}
</style>
