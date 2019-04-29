<template>
	<AppContent :class="{ loading: loading || isManualSave }">
		<div v-if="note && !loading" id="note-editor"
			class="note-editor" :class="{ fullscreen: fullscreen }"
			@keyup.ctrl.83.prevent.stop="onManualSave"
			@keyup.meta.83.prevent.stop="onManualSave"
		>
			<div v-show="!note.content" class="placeholder">
				{{ tn('Write ...') }}
			</div>
			<TheEditor :value="note.content" @input="onEdit" />
			<span class="action-buttons">
				<button class="icon-details btn-sidebar" @click="onToggleSidebar" />
				<button class="icon-fullscreen btn-fullscreen" @click="onToggleDistractionFree" />
			</span>
		</div>
	</AppContent>
</template>
<script>

import {
	AppContent,
} from 'nextcloud-vue'
import TheEditor from './EditorEasyMDE'
import NotesService from './NotesService'
import store from './store'

export default {
	name: 'Note',

	components: {
		AppContent,
		TheEditor,
	},

	props: {
		noteId: {
			type: String,
			required: true,
		},
	},

	data: function() {
		return {
			loading: false,
			fullscreen: false,
		}
	},

	computed: {
		note() {
			return store.getters.getNote(parseInt(this.noteId))
		},
		title() {
			return this.note ? this.note.title : ''
		},
		isManualSave() {
			return store.state.isManualSave
		},
	},

	watch: {
		// call again the method if the route changes
		'$route': 'fetchData',
		title: 'onUpdateTitle',
	},

	created() {
		this.fetchData()
		// TODO move the following from jQuery to plain JS
		$(document).bind('webkitfullscreenchange mozfullscreenchange fullscreenchange', this.onDetectFullscreen)
	},

	destroyed() {
		store.commit('setSidebarOpen', false)
		this.onUpdateTitle(null)
	},

	methods: {
		fetchData() {
			store.commit('setSidebarOpen', false)
			this.onUpdateTitle(this.title)
			this.loading = true
			NotesService.fetchNote(this.noteId)
				.then(note => {
					this.loading = false
				})
				.catch(err => {
					console.error(err)
					// TODO error handling: show error and open another note
				})
		},

		onUpdateTitle(title) {
			let defaultTitle = store.state.documentTitle
			if (title) {
				document.title = title + ' - ' + defaultTitle
			} else {
				document.title = defaultTitle
			}
		},

		onDetectFullscreen() {
			this.fullscreen = document.fullScreen || document.mozFullScreen || document.webkitIsFullScreen
		},

		onToggleDistractionFree() {
			function launchIntoFullscreen(element) {
				if (element.requestFullscreen) {
					element.requestFullscreen()
				} else if (element.mozRequestFullScreen) {
					element.mozRequestFullScreen()
				} else if (element.webkitRequestFullscreen) {
					element.webkitRequestFullscreen()
				} else if (element.msRequestFullscreen) {
					element.msRequestFullscreen()
				}
			}

			function exitFullscreen() {
				if (document.exitFullscreen) {
					document.exitFullscreen()
				} else if (document.mozCancelFullScreen) {
					document.mozCancelFullScreen()
				} else if (document.webkitExitFullscreen) {
					document.webkitExitFullscreen()
				}
			}

			if (this.fullscreen) {
				exitFullscreen()
			} else {
				launchIntoFullscreen(document.getElementById('note-editor'))
			}
		},

		onToggleSidebar() {
			store.commit('setSidebarOpen', !store.state.sidebarOpen)
		},

		onEdit(newContent) {
			if (this.note.content !== newContent) {
				let note = {
					...this.note,
					content: newContent,
					unsaved: true,
				}
				store.commit('add', note)
				setTimeout(NotesService.saveNote.bind(NotesService, note.id), 1000)
			}
		},

		// TODO register shortcut CTRL+S globally
		onManualSave() {
			NotesService.saveNoteManually(this.note.id)
		},
	},
}
</script>
<style scoped>
.note-editor {
	min-height: 100%;
	background-color: var(--color-main-background);
}

/* distraction free styles */
.note-editor.fullscreen {
	width: 100vw;
	height: 100vh;
	overflow-y: auto;
	background-color: var(--color-main-background);
}

/* placeholder */
.placeholder {
	position: absolute;
	padding: 2em;
	font-size: 16px;
	opacity: 0.5;
}

/* main editor button */
.action-buttons {
	position: sticky;
	bottom: 5px;
	left: 100%;
	z-index: 2000;
	display: flex;
	justify-content: space-around;
	width: 100px;
}

.action-buttons button {
	padding: 14px;
	width: 40px;
	height: 40px;
	margin: 0;
}
</style>
