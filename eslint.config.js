import { recommended } from '@nextcloud/eslint-config'

export default [
	...recommended,
	{
		rules: {
		// do not require JSDoc comments
			'jsdoc/require-jsdoc': 'off',
		},
	},
	{
		files: ['**/*.vue'],
		rules: {
		// no ending html tag on a new line (was warn in "vue/strongly-recommended")
			'vue/html-closing-bracket-newline': ['error', { multiline: 'always' }],
			// allow first attribute in new line if multiline
			'vue/first-attribute-linebreak': ['error', {
				singleline: 'beside',
				multiline: 'ignore',
			}],
		},
	},
]
