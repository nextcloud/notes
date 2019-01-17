import '@babel/polyfill'

import Vue from 'vue'
import App from './App'
import store from './store'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA


// TODO Disable on production
Vue.config.devTools = true
Vue.config.performance = true


/* eslint-disable-next-line no-new */
new Vue({
	store,
	render: h => h(App),
}).$mount('#content')
