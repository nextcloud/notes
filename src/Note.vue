<template>
	<div class="note-wrapper" :class="{ loading: loading || isManualSave }"
		@keyup.ctrl.83.prevent.stop="onManualSave"
		@keyup.meta.83.prevent.stop="onManualSave"
	>
		<div v-if="note && !loading" id="note-editor" class="note-editor">
			<TheEditor :value="note.content" @input="onEdit" />
			<span class="action-buttons">
				<button class="icon-fullscreen btn-fullscreen" @click="onToggleDistractionFree" />
			</span>
		</div>
		<StatusBar v-if="note && !loading" :note="note" class="note-status-bar" />
	</div>
</template>
<script>

import TheEditor from './EditorTUI'
import NotesService from './NotesService'
import StatusBar from './StatusBar'
import store from './store'

export default {
	name: 'Note',

	components: {
		TheEditor,
		StatusBar,
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
	},

	destroyed() {
		this.onUpdateTitle(null)
	},

	methods: {
		fetchData() {
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

			if (document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement) {
				exitFullscreen()
			} else {
				launchIntoFullscreen(document.getElementById('note-editor'))
			}
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

		onManualSave() {
			let note = {
				...this.note,
				error: false,
			}
			store.commit('add', note)
			NotesService.saveNote(note.id, true)
		},
	},
}
</script>
<style scoped>
.note-editor {
	background-color: var(--color-main-background);
}

.action-buttons {
	position: fixed;
	bottom: 4px;
	right: 20px;
	z-index: 2000;
}

.btn-fullscreen {
	padding: 15px;
}
</style>
