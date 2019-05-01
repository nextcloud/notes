import Vue from 'vue'
import Router from 'vue-router'
import Welcome from './components/Welcome'
import Note from './components/Note'
import Sidebar from './components/Sidebar'

Vue.use(Router)

export default new Router({
	mode: 'history',
	base: OC.generateUrl('/apps/notes'),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/welcome',
			name: 'welcome',
			component: Welcome,
		},
		{
			path: '/note/:noteId',
			name: 'note',
			components: {
				default: Note,
				sidebar: Sidebar,
			},
			props: {
				default: true,
				sidebar: true,
			},
		},
	],
})
