<template>
	<p>{{ value }}</p>
</template>
<script>

export default {
	name: 'TheEditor',

	props: {
		value: {
			type: String,
			required: true,
		},
	},
}
</script>
