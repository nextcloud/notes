import Vue from 'vue'
import App from './App'
import router from './router'
import store from './store'

Vue.mixin({ methods: { t, n } })

export default new Vue({
	el: '#content',
	store,
	router,
	render: h => h(App),
})
