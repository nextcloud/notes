module.exports = {
	extends: [
		'nextcloud',
	],
	globals: {
		$: 'readonly',
	},
	rules: {
		// no ending html tag on a new line (was warn in "vue/strongly-recommended")
		'vue/html-closing-bracket-newline': ['error', { multiline: 'always' }]
	},
}
