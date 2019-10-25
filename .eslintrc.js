module.exports = {
	extends: [
		'nextcloud',
	],
	globals: {
		$: 'readonly',
	},
	rules: {
		// always add a trailing comma, for diff readability (was "never" in "standard")
		'comma-dangle': ['warn', 'always-multiline'],

		// disallow use of "var" (not in "standard")
		'no-var': 'error',
		// Suggest using const
		'prefer-const': 'warn',

		// no ending html tag on a new line (was warn in "vue/strongly-recommended")
		'vue/html-closing-bracket-newline': ['error', { multiline: 'always' }]
	},
}
