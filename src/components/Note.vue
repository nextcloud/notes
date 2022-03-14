<template>
	<AppContent :class="{ loading: loading || isManualSave, 'icon-error': !loading && (!note || note.error), 'sidebar-open': sidebarOpen }">
		<div v-show="!loading && note && !note.error && !note.deleting"
			id="note-container"
			class="note-container"
			:class="{ fullscreen: fullscreen }"
		>
			<Modal v-if="note.conflict && showConflict" size="full" @close="showConflict=false">
				<div class="conflict-modal">
					<div class="conflict-header">
						{{ t('notes', 'The note has been changed in another session. Please choose which version should be saved.') }}
					</div>
					<div class="conflict-solutions">
						<ConflictSolution
							:content="note.conflict.content"
							:reference="note.reference.content"
							:button="t('notes', 'Use version from server')"
							@onChooseSolution="onUseRemoteVersion"
						/>
						<ConflictSolution
							:content="note.content"
							:reference="note.reference.content"
							:button="t('notes', 'Use current version')"
							@onChooseSolution="onUseLocalVersion"
						/>
					</div>
				</div>
			</Modal>
			<div class="note-editor">
				<TheSidemenu ref="TheSidemenu" />
				<div v-show="!note.content" class="placeholder" :class="preview ? '' : 'placeholder-toolbar'">
					{{ preview ? t('notes', 'Empty note') : t('notes', 'Write …') }}
				</div>
				<ThePreview v-if="preview" :value="note.content" :noteid="noteId" />
				<TheEditor v-else
					ref="TheEditor"
					:value="note.content"
					:noteid="noteId"
					:readonly="note.readonly"
					@input="onEdit"
					@add-mMnu-item="addMenuItem"
				/>
			</div>
		</div>
	</AppContent>
</template>
<script>

import {
	Actions,
	ActionButton,
	AppContent,
	Modal,
	Tooltip,
	isMobile,
} from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'

import SyncAlertIcon from 'vue-material-design-icons/SyncAlert'
import PencilOffIcon from 'vue-material-design-icons/PencilOff'

import { config } from '../config'
import { fetchNote, refreshNote, saveNoteManually, queueCommand, conflictSolutionLocal, conflictSolutionRemote } from '../NotesService'
import { routeIsNewNote } from '../Util'
import TheEditor from './EditorEasyMDE'
import ThePreview from './EditorMarkdownIt'
import TheSidemenu from './Sidemenu'
import ConflictSolution from './ConflictSolution'
import store from '../store'

export default {
	name: 'Note',

	components: {
		Actions,
		ActionButton,
		AppContent,
		ConflictSolution,
		Modal,
		PencilOffIcon,
		SyncAlertIcon,
		TheEditor,
		ThePreview,
		TheSidemenu,
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
		state: {
			type: String,
		},
	},

	data() {
		return {
			loading: false,
			fullscreen: false,
			preview: false,
			actionsOpen: false,
			autosaveTimer: null,
			autotitleTimer: null,
			refreshTimer: null,
			etag: null,
			showConflict: false,
			sidemenuitemkeys: [],
			newMenuItems: [],
		}
	},

	mounted() {
		// todo fix this. The timeout is not reliable or a good way to do this.
		// it breaks often, VERY often.
		setTimeout(() => {
			const sidemenu = this.$refs.TheSidemenu
			this.sidemenuitemkeys['details'] = sidemenu.addEntry(t('notes', 'Details'), 'icon-details', this.onToggleSidebar)

			if (this.preview) {
				this.sidemenuitemkeys['preview'] = sidemenu.addEntry(t('notes', 'Edit'), 'icon-rename', this.onTogglePreview)
			} else {
				this.sidemenuitemkeys['preview'] = sidemenu.addEntry(t('notes', 'Preview'), 'icon-toggle', this.onTogglePreview)
			}

			if (this.fullscreen) {
				this.sidemenuitemkeys['fullscreen'] = sidemenu.addEntry(t('notes', 'Exit full screen'), 'icon-fullscreen', this.onToggleDistractionFree)
			} else {
				this.sidemenuitemkeys['fullscreen'] = sidemenu.addEntry(t('notes', 'Full screen'), 'icon-fullscreen', this.onToggleDistractionFree)
			}

			const self = this
			this.newMenuItems.forEach(function(element) {
				self.addMenuItem(element.title, element.icon, element.callback, element.group, element.hidden, self)
			})
		}, 1000)
	},

	computed: {
		note() {
			return store.getters.getNote(parseInt(this.noteId))
		},
		title() {
			return this.note ? this.note.title : ''
		},
		isNewNote() {
			return routeIsNewNote(this.$route)
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
		'note.conflict'(newConflict, oldConflict) {
			if (newConflict) {
				this.showConflict = true
			}
		},
	},

	created() {
		this.fetchData()
		document.addEventListener('webkitfullscreenchange', this.onDetectFullscreen)
		document.addEventListener('mozfullscreenchange', this.onDetectFullscreen)
		document.addEventListener('fullscreenchange', this.onDetectFullscreen)
		document.addEventListener('keydown', this.onKeyPress)
		document.addEventListener('visibilitychange', this.onVisibilityChange)
	},

	destroyed() {
		this.stopRefreshTimer()
		document.removeEventListener('webkitfullscreenchange', this.onDetectFullscreen)
		document.removeEventListener('mozfullscreenchange', this.onDetectFullscreen)
		document.removeEventListener('fullscreenchange', this.onDetectFullscreen)
		document.removeEventListener('keydown', this.onKeyPress)
		document.removeEventListener('visibilitychange', this.onVisibilityChange)
		this.onUpdateTitle(null)
	},

	methods: {
		fetchData() {
			this.etag = null
			this.stopRefreshTimer()

			if (this.isMobile) {
				emit('toggle-navigation', { open: false })
			}

			this.onUpdateTitle(this.title)
			this.loading = true
			this.preview = store.state.app.settings.noteMode === 'preview'
			fetchNote(parseInt(this.noteId))
				.then((note) => {
					if (note.error) {
						showError(t('notes', 'Error from Nextcloud server: {msg}', { msg: note.errorType }))
					}
					this.startRefreshTimer()
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

			const menuid = this.sidemenuitemkeys['preview']
			const sidemenu = this.$refs.TheSidemenu
			if (this.preview) {
				sidemenu.setGroupEnabled('editor', false)
				sidemenu.updateEntry(menuid, 'title', t('notes', 'Preview'), false)
				sidemenu.updateEntry(menuid, 'icon', 'icon-toggle')
			} else {
				sidemenu.setGroupEnabled('editor', true)
				sidemenu.updateEntry(menuid, 'title', t('notes', 'Edit'), false)
				sidemenu.updateEntry(menuid, 'icon', 'icon-rename')
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

			const menuid = this.sidemenuitemkeys['fullscreen']
			if (this.fullscreen) {
				exitFullscreen()
				this.$refs.TheSidemenu.updateEntry(menuid, 'title', t('notes', 'Full screen'))
			} else {
				launchIntoFullscreen(document.getElementById('note-container'))
				this.$refs.TheSidemenu.updateEntry(menuid, 'title', t('notes', 'Exit full screen'))
			}
			this.actionsOpen = false
		},

		onToggleSidebar() {
			store.commit('setSidebarOpen', !store.state.app.sidebarOpen)
			this.actionsOpen = false
		},

		onVisibilityChange() {
			if (document.visibilityState === 'visible') {
				this.stopRefreshTimer()
				this.refreshNote()
			}
		},

		stopRefreshTimer() {
			if (this.refreshTimer !== null) {
				clearTimeout(this.refreshTimer)
				this.refreshTimer = null
			}
		},

		startRefreshTimer() {
			this.stopRefreshTimer()
			const interval = document.visibilityState === 'visible' ? config.interval.note.refresh : config.interval.note.refreshHidden
			this.refreshTimer = setTimeout(() => {
				this.refreshTimer = null
				this.refreshNote()
			}, interval * 1000)
		},

		refreshNote() {
			if (this.note.unsaved && !this.note.conflict) {
				this.startRefreshTimer()
				return
			}
			refreshNote(parseInt(this.noteId), this.etag).then(etag => {
				if (etag) {
					this.etag = etag
					this.$forceUpdate()
				}
				this.startRefreshTimer()
			})
		},

		onEdit(newContent) {
			if (this.note.content !== newContent) {
				this.stopRefreshTimer()
				const note = {
					...this.note,
					content: newContent,
					unsaved: true,
				}
				store.commit('updateNote', note)
				this.$forceUpdate()

				// queue auto saving note content
				if (this.autosaveTimer === null) {
					this.autosaveTimer = setTimeout(() => {
						this.autosaveTimer = null
						queueCommand(note.id, 'content')
					}, config.interval.note.autosave * 1000)
				}

				// (re-) start auto refresh timer
				// TODO should be after save is finished
				this.startRefreshTimer()

				// stop old autotitle timer
				if (this.autotitleTimer !== null) {
					clearTimeout(this.autotitleTimer)
					this.autotitleTimer = null
				}
				// start autotitle timer if note is new
				if (this.isNewNote) {
					this.autotitleTimer = setTimeout(() => {
						this.autotitleTimer = null
						if (this.isNewNote) {
							queueCommand(note.id, 'autotitle')
						}
					}, config.interval.note.autotitle * 1000)
				}
			}
		},

		onKeyPress(event) {
			if (event.ctrlKey || event.metaKey) {
				switch (event.key.toLowerCase()) {
				case 's':
					event.preventDefault()
					this.onManualSave()
					break
				case '/':
					event.preventDefault()
					this.onTogglePreview()
					break
				}
			}
		},

		onManualSave() {
			const note = {
				...this.note,
			}
			store.commit('updateNote', note)
			saveNoteManually(this.note.id)
		},

		onUseLocalVersion() {
			conflictSolutionLocal(this.note)
			this.showConflict = false
		},

		onUseRemoteVersion() {
			conflictSolutionRemote(this.note)
			this.showConflict = false
		},
		updateNoteState(manual) {
			if (manual !== '') {
				this.state = manual
				return
			}
			if (this.loading) {
				this.state = t('notes', 'Loading...')
				return
			}
			this.state = ''
		},

		addMenuItem(title, icon, callback, group, hidden = true, self = this) {
			// it takes a while for sidebar to be set up.
			// if it is not yet set up, add it to the queue which gets processed when
			// the sidemenu was set up properly.
			const sidemenu = self.$refs.TheSidemenu
			if (sidemenu) {
				const id = title + icon + group
				if (typeof self.sidemenuitemkeys[id] !== 'undefined') {
					sidemenu.removeID(self.sidemenuitemkeys[id])
				}
				self.sidemenuitemkeys[id] = sidemenu.addEntry(title, icon, callback, group, hidden)
			} else {
				self.newMenuItems.push({ title: title, icon: icon, callback: callback, group: group, hidden: hidden })
			}
		},
	},
}
</script>
<style scoped>
.note-container {
	min-height: 100%;
	background-color: var(--color-main-background);
}

.note-editor {
	max-width: 47em;
	font-size: 16px;
	padding-top: 1em;
	padding-bottom: 1em;

	margin-left: auto;
	margin-right: auto;
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

.placeholder-toolbar {
	padding-top: 4.5em;
}

.note-container.fullscreen .action-buttons {
	top: 0px;
}
/* Conflict Modal */
.conflict-modal {
	width: 70vw;
}

.conflict-header {
	padding: 1ex 1em;
}

.conflict-solutions {
	display: flex;
	flex-direction: row-reverse;
	max-height: 75vh;
	overflow-y: auto;
}

@media (max-width: 60em) {
	.conflict-solutions {
		flex-direction: column;
	}
}

</style>
