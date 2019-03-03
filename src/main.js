import '@babel/polyfill'

import Vue from 'vue'
import App from './App'
import router from './router'
import store from './store'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA

export default new Vue({
	el: '#content',
	store,
	router,
	render: h => h(App),
})
