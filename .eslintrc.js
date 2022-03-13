module.exports = {
	extends: [
		'@nextcloud',
	],
	rules: {
		// no ending html tag on a new line (was warn in "vue/strongly-recommended")
		'vue/html-closing-bracket-newline': ['error', { multiline: 'always' }],
		// do not require JSDoc comments
		'jsdoc/require-jsdoc': 'off',
		// allow first attribute in new line if multiline
		'vue/first-attribute-linebreak': ['error', {
			'singleline': 'beside',
			'multiline': 'ignore',
		}],
	},
}
