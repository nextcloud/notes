import '@babel/polyfill'

import Vue from 'vue'
import App from './App'
import router from './router'
import store from './store'
import AppGlobal from './mixins/AppGlobal'

Vue.mixin(AppGlobal)

export default new Vue({
	el: '#content',
	store,
	router,
	render: h => h(App),
})
