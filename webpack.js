const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

webpackConfig.entry.dashboard = path.join(__dirname, 'src', 'dashboard.js')

module.exports = webpackConfig
