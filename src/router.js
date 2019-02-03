import Vue from 'vue'
import Router from 'vue-router'
import Welcome from './Welcome'
import Note from './Note'

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
			component: Note,
			props: true,
		},
	],
})
