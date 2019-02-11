<template>
	<app-navigation-settings :title="t('notes', 'Settings')">
		TODO: settings
	</app-navigation-settings>
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
