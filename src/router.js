import Vue from 'vue'
import Router from 'vue-router'
import { generateUrl } from '@nextcloud/router'

import Loading from './components/Loading'
import Welcome from './components/Welcome'
import NotesList from './components/NotesList'
import Note from './components/Note'
import Sidebar from './components/Sidebar'

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
				notes: NotesList,
				default: Note,
				sidebar: Sidebar,
			},
			props: {
				notes: true,
				default: true,
				sidebar: true,
			},
		},
	],
})
