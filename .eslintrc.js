module.exports = {
	root: true,
	parserOptions: {
		parser: 'babel-eslint',
		ecmaVersion: 6,
	},
	settings: {
		'import/resolver': {
			webpack: {
				config: 'webpack.common.js',
			},
			node: {
				paths: ['src'],
				extensions: ['.js', '.vue'],
			},
		},
	},
	extends: [
		'eslint:recommended',
		'plugin:import/recommended',
		'plugin:vue/recommended',
		'standard',
	],
	globals: {
		$: 'readonly',
		t: 'readonly',
		n: 'readonly',
		OC: 'readonly',
		OCA: 'readonly',
	},
	plugins: [
		'vue',
	],
	rules: {
		// allow space before function () (was "always" in "standard")
		'space-before-function-paren': ['error', 'never'],
		// stay consistent with array brackets (not in "standard")
		'array-bracket-newline': ['error', 'consistent'],

		// tabs only (was spaces in "standard")
		'indent': ['error', 'tab'],
		// allow tabs for indentation (was forbidden in "standard")
		'no-tabs': ['error', { allowIndentationTabs: true }],
		// indentation in vue's html should be tabs (was spaces in "vue/strongly-recommended")
		'vue/html-indent': ['error', 'tab'],

		// only debug console (not in "standard")
		'no-console': ['error', { allow: ['error', 'warn', 'info', 'debug'] }],
		// always add a trailing comma, for diff readability (was "never" in "standard")
		'comma-dangle': ['warn', 'always-multiline'],
		// always have the operator in front (was "after" in "standard")
		'operator-linebreak': ['error', 'before'],
		// ternary on multiline (not in "standard")
		'multiline-ternary': ['error', 'always-multiline'],

		// disallow use of "var" (not in "standard")
		'no-var': 'error',
		// Suggest using const
		'prefer-const': 'warn',

		// check case of component names (not in "vue/recommended")
		'vue/component-name-in-template-casing': 'error',
		// no ending html tag on a new line (was warn in "vue/strongly-recommended")
		'vue/html-closing-bracket-newline': 'error',
		// space before self-closing elements (was warn in "vue/strongly-recommended")
		'vue/html-closing-bracket-spacing': 'error',
		// code spacing with attributes (default is 1)
		'vue/max-attributes-per-line': [
			'error',
			{
				singleline: 3,
				multiline: {
					max: 3,
					allowFirstLine: true,
				}
			}
		],
	},
}
