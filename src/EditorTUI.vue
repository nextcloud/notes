<template>
	<Editor :value="value" :options="options" height="100%"
		@input="$emit('input', $event)"
	/>
</template>
<script>

import 'tui-editor/dist/tui-editor.css'
import 'tui-editor/dist/tui-editor-contents.css'
import 'codemirror/lib/codemirror.css'
import Editor from '@toast-ui/vue-editor/src/Editor.vue'

export default {
	name: 'TheEditor',

	components: {
		Editor,
	},

	props: {
		value: {
			type: String,
			required: true,
		},
	},

	data: function() {
		return {
			options: {
				usageStatistics: false,
				hideModeSwitch: true,
				toolbarItems: [],
			},
		}
	},
}
</script>
<style>
.te-toolbar-section {
	display: none;
}

.CodeMirror {
	font-family: inherit;
}
</style>
