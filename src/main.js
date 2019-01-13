import '@babel/polyfill'

import Vue from 'vue'
import App from './App'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA


// TODO Disable on production
Vue.config.devtools = true
Vue.config.performance = true


/* eslint-disable-next-line no-new */
new Vue({
	render: h => h(App)
}).$mount('#content')
