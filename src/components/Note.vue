<template>
	<AppContent :class="{ loading: loading || isManualSave, 'icon-error': !loading && (!note || note.error), 'sidebar-open': sidebarOpen }">
		<div v-if="!loading && note && !note.error && !note.deleting"
			id="note-container"
			class="note-container"
			:class="{ fullscreen: fullscreen }"
		>
			<div class="note-editor">
				<div v-show="!note.content" class="placeholder">
					{{ preview ? t('notes', 'Empty note') : t('notes', 'Write …') }}
				</div>
				<ThePreview v-if="preview" :value="note.content" />
				<TheEditor v-else :value="note.content" @input="onEdit" />
			</div>
			<span class="action-buttons">
				<Actions :open.sync="actionsOpen" menu-align="right">
					<ActionButton v-show="!sidebarOpen && !fullscreen"
						icon="icon-details"
						@click="onToggleSidebar"
					>
						{{ t('notes', 'Details') }}
					</ActionButton>
					<ActionButton v-if="!preview"
						icon="icon-toggle"
						@click="onTogglePreview"
					>
						{{ t('notes', 'Preview') }}
					</ActionButton>
					<ActionButton v-else
						icon="icon-rename"
						@click="onTogglePreview"
					>
						{{ t('notes', 'Edit') }}
					</ActionButton>
					<ActionButton
						icon="icon-fullscreen"
						:class="{ active: fullscreen }"
						@click="onToggleDistractionFree"
					>
						{{ fullscreen ? t('notes', 'Exit full screen') : t('notes', 'Full screen') }}
					</ActionButton>
				</Actions>
				<button v-show="note.saveError"
					v-tooltip.right="t('notes', 'Save failed. Click to retry.')"
					class="action-error icon-error-color"
					@click="onManualSave"
				/>
			</span>
		</div>
	</AppContent>
</template>
<script>

import {
	Actions,
	ActionButton,
	AppContent,
	Tooltip,
	isMobile,
} from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'

import { fetchNote, saveNote, saveNoteManually, routeIsNewNote } from '../NotesService'
import TheEditor from './EditorEasyMDE'
import ThePreview from './EditorMarkdownIt'
import store from '../store'

export default {
	name: 'Note',

	components: {
		Actions,
		ActionButton,
		AppContent,
		TheEditor,
		ThePreview,
	},

	directives: {
		tooltip: Tooltip,
	},

	mixins: [isMobile],

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
			autosaveTimer: null,
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
			return store.state.app.isManualSave
		},
		sidebarOpen() {
			return store.state.app.sidebarOpen
		},
	},

	watch: {
		$route(to, from) {
			if (to.name !== from.name || to.params.noteId !== from.params.noteId) {
				this.fetchData()
			}
		},
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

			if (this.isMobile) {
				emit('toggle-navigation', { open: false })
			}

			this.onUpdateTitle(this.title)
			this.loading = true
			this.preview = false
			fetchNote(this.noteId)
				.then((note) => {
					if (note.errorMessage) {
						showError(note.errorMessage)
					}
				})
				.catch(() => {
					// note not found
				})
				.then(() => {
					this.loading = false
				})
		},

		onUpdateTitle(title) {
			const defaultTitle = store.state.app.documentTitle
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
			store.commit('setSidebarOpen', !store.state.app.sidebarOpen)
			this.actionsOpen = false
		},

		onEdit(newContent) {
			if (this.note.content !== newContent) {
				const note = {
					...this.note,
					content: newContent,
					unsaved: true,
					autotitle: routeIsNewNote(this.$route),
				}
				store.commit('updateNote', note)
				if (this.autosaveTimer === null) {
					this.autosaveTimer = setTimeout(() => {
						this.autosaveTimer = null
						saveNote(note.id)
					}, 2000)
				}
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
			saveNoteManually(this.note.id)
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
	padding: 1em;
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
	padding: 1em;
	opacity: 0.5;
}

/* main editor button */
.action-buttons {
	position: fixed;
	top: 50px;
	right: 20px;
	width: 44px;
	margin-top: 1em;
	z-index: 2000;
}

.action-buttons .action-error {
	width: 44px;
	height: 44px;
}

.note-container.fullscreen .action-buttons {
	top: 0px;
}

.action-buttons button {
	padding: 15px;
}
</style>
