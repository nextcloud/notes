<template>
	<AppContent :class="{ loading: loading || isManualSave, 'icon-error': !loading && (!note || note.error), 'sidebar-open': sidebarOpen }">
		<div v-if="!loading && note && !note.error" id="note-container"
			class="note-container" :class="{ fullscreen: fullscreen }"
		>
			<div class="note-editor">
				<div v-show="!note.content" class="placeholder">
					{{ t('notes', 'Write …') }}
				</div>
				<ThePreview v-if="preview" :value="note.content" />
				<TheEditor v-else :value="note.content" @input="onEdit" />
			</div>
			<span class="action-buttons">
				<button v-show="note.saveError"
					v-tooltip="t('notes', 'Save failed. Click to retry.')"
					class="icon-error-color"
					@click="onManualSave"
				/>
				<button v-show="actionsOpen && !fullscreen"
					v-tooltip="t('notes', 'Toggle sidebar')"
					class="icon-details"
					@click="onToggleSidebar"
				/>
				<button v-show="actionsOpen"
					v-tooltip="t('notes', 'Toggle preview')"
					class="icon-toggle"
					:class="{ active: preview }"
					@click="onTogglePreview"
				/>
				<button v-show="actionsOpen"
					v-tooltip="t('notes', 'Toggle fullscreen mode')"
					class="icon-fullscreen"
					:class="{ active: fullscreen }"
					@click="onToggleDistractionFree"
				/>
				<button
					v-tooltip="t('notes', 'Toggle action menu')"
					class="icon-more"
					:class="{ active: actionsOpen }"
					@click="onToggleActions"
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
import ThePreview from './EditorMarkdownIt'
import NotesService from '../NotesService'
import store from '../store'

export default {
	name: 'Note',

	components: {
		AppContent,
		TheEditor,
		ThePreview,
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
			preview: false,
			actionsOpen: false,
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
		sidebarOpen() {
			return store.state.sidebarOpen
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
			this.preview = false
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
			const defaultTitle = store.state.documentTitle
			if (title) {
				document.title = title + ' - ' + defaultTitle
			} else {
				document.title = defaultTitle
			}
		},

		onTogglePreview() {
			this.preview = !this.preview
			this.actionsOpen = false
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
			this.actionsOpen = false
		},

		onToggleSidebar() {
			store.commit('setSidebarOpen', !store.state.sidebarOpen)
			this.actionsOpen = false
		},

		onToggleActions() {
			this.actionsOpen = !this.actionsOpen
		},

		onEdit(newContent) {
			if (this.note.content !== newContent) {
				const note = {
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
	padding: 0 1em;
}

/* center editor on large screens */
@media (min-width: 1600px) {
	.note-editor {
		margin: 0 auto;
	}
	.note-container {
		padding-right: 250px;
		transition-duration: var(--animation-quick);
		transition-property: padding-right;
	}
	.sidebar-open .note-container {
		padding-right: 0px;
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
