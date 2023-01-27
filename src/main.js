import Vue from 'vue'
import App from './App.vue'
import router from './router.js'
import store from './store.js'

Vue.mixin({ methods: { t, n } })

export default new Vue({
	el: '#content',
	store,
	router,
	render: h => h(App),
})
