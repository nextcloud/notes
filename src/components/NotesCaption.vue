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
		padding-left: 10px;
		padding-top: 5px;
		padding-bottom: 5px;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
</style>
