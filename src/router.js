import Vue from 'vue'
import Router from 'vue-router'
import { generateUrl } from '@nextcloud/router'

import Loading from './components/Loading.vue'
import Welcome from './components/Welcome.vue'
import NotesView from './components/NotesView.vue'
import Sidebar from './components/Sidebar.vue'

Vue.use(Router)

export default new Router({
	mode: 'history',
	base: generateUrl('apps/notes'),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/',
			name: 'loading',
			component: Loading,
		},
		{
			path: '/welcome',
			name: 'welcome',
			component: Welcome,
		},
		{
			path: '/note/:noteId',
			name: 'note',
			components: {
				default: NotesView,
				sidebar: Sidebar,
			},
			props: {
				default: true,
				sidebar: true,
			},
		},
	],
})
