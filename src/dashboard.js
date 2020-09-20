import Vue from 'vue'
import Dashboard from './components/Dashboard'

Vue.mixin({ methods: { t, n } })

document.addEventListener('DOMContentLoaded', () => {
	OCA.Dashboard.register('notes', (el) => {
		const View = Vue.extend(Dashboard)
		new View().$mount(el)
	})
})
