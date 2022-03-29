<template>
	<div class="title">
		{{ title }}
	</div>
</template>

<script>
export default {
	name: 'NotesGroup',

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
	}
</style>
