const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

webpackConfig.entry.dashboard = path.join(__dirname, 'src', 'dashboard.js')

webpackConfig.optimization = {
	splitChunks: {
		cacheGroups: {
			defaultVendors: {
				name: 'commons',
			}
		},
	},
}

module.exports = webpackConfig
