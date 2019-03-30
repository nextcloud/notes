<template>
	<AppNavigationSettings :title="t('notes', 'Settings')">
		<p>TODO: settings</p>
	</AppNavigationSettings>
</template>

<script>
import {
	AppNavigationSettings,
} from 'nextcloud-vue'

export default {
	name: 'AppSettings',

	components: {
		AppNavigationSettings,
	},

	data: function() {
		return {
		}
	},

	computed: {
	},

	created() {
	},

	methods: {
	},
}
</script>
