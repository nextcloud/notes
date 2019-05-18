<template>
	<AppContent :class="{ loading: loading || isManualSave, 'icon-error': !loading && (!note || note.error) }">
		<div v-if="!loading && note && !note.error" id="note-container"
			class="note-container" :class="{ fullscreen: fullscreen }"
		>
			<div v-show="!note.content" class="placeholder">
				{{ t('notes', 'Write') }} …
			</div>
			<TheEditor class="note-editor" :value="note.content" @input="onEdit" />
			<span class="action-buttons">
				<button v-show="note.saveError"
					v-tooltip="t('notes', 'Save failed. Click to retry.')"
					class="icon-error-color"
					@click="onManualSave"
				/>
				<button v-show="!fullscreen"
					v-tooltip="t('notes', 'Toggle sidebar')"
					class="icon-details"
					@click="onToggleSidebar"
				/>
				<button
					v-tooltip="t('notes', 'Toggle fullscreen mode')"
					class="icon-fullscreen"
					@click="onToggleDistractionFree"
				/>
			</span>
		</div>
	</AppContent>
</template>
<script>

import {
	AppContent,
	Tooltip,
} from 'nextcloud-vue'
import TheEditor from './EditorEasyMDE'
import NotesService from '../NotesService'
import store from '../store'

export default {
	name: 'Note',

	components: {
		AppContent,
		TheEditor,
	},

	directives: {
		tooltip: Tooltip,
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
		$(document).bind('keypress.notes.save', this.onKeyPress)
	},

	destroyed() {
		$(document).unbind('keypress.notes.save')
		store.commit('setSidebarOpen', false)
		this.onUpdateTitle(null)
	},

	methods: {
		fetchData() {
			store.commit('setSidebarOpen', false)
			this.onUpdateTitle(this.title)
			this.loading = true
			NotesService.fetchNote(this.noteId)
				.then((note) => {
					if (note.errorMessage) {
						OC.Notification.showTemporary(note.errorMessage)
					}
				})
				.catch(() => {
					// note not found
				})
				.finally(() => {
					this.loading = false
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
				launchIntoFullscreen(document.getElementById('note-container'))
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

		onKeyPress(event) {
			if (event.ctrlKey || event.metaKey) {
				switch (String.fromCharCode(event.which).toLowerCase()) {
				case 's':
					event.preventDefault()
					this.onManualSave()
					break
				}
			}
		},

		onManualSave() {
			NotesService.saveNoteManually(this.note.id)
		},
	},
}
</script>
<style scoped>
.note-container {
	min-height: 100%;
	width: 100%;
	background-color: var(--color-main-background);
}

.note-editor {
	max-width: 47em;
	font-size: 16px;
}

/* center editor on large screens */
@media (min-width: 1600px) {
	.note-editor {
		margin: 0 auto;
	}
	.note-container {
		padding-right: 250px;
	}
}

/* distraction free styles */
.note-container.fullscreen {
	width: 100vw;
	height: 100vh;
	overflow-y: auto;
	padding: 0;
}

.note-container.fullscreen .note-editor {
	margin: 0 auto;
}

/* placeholder */
.placeholder {
	position: absolute;
	padding: 2em;
	opacity: 0.5;
}

/* main editor button */
.action-buttons {
	position: fixed;
	bottom: 4px;
	right: 20px;
	z-index: 2000;
}

.action-buttons button {
	padding: 15px;
}
</style>
