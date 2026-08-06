<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSidebar
		v-model:active="activeTab"
		data-cy-notes-share-sidebar
		forceMenu
		:loading="isOpen && loading"
		:name="note?.title || t('notes', 'Note')"
		noToggle
		:open="isOpen"
		@closed="onClosed"
		@update:open="onToggle"
	>
		<NcAppSidebarTab v-if="note"
			id="notes-info"
			:name="t('notes', 'Note info')"
			:order="0"
		>
			<template #icon>
				<InformationOutlineIcon :size="20" />
			</template>
			<NoteInfo :note="note" :node="currentNode" :contentLoading="loadingContent" />
		</NcAppSidebarTab>

		<NcAppSidebarTab v-for="tab in tabs"
			:id="tab.id"
			:key="tab.id"
			:name="tab.displayName"
			:order="tab.order"
		>
			<template #icon>
				<NcIconSvgWrapper :svg="tab.iconSvgInline" />
			</template>

			<NcEmptyContent v-if="loading">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>

			<NcEmptyContent v-else-if="!currentNode || error">
				<template #icon>
					<FileOutlineIcon :size="44" />
				</template>
				{{ error || t('notes', 'Unable to load the selected note.') }}
			</NcEmptyContent>

			<component
				:is="tab.tagName"
				v-else
				:active.prop="activeTab === tab.id"
				:folder.prop="currentFolder"
				:node.prop="currentNode"
				:view.prop="currentView"
			/>
		</NcAppSidebarTab>

		<NcEmptyContent v-if="isOpen && tabs.length === 0 && !note">
			<template #icon>
				<FileOutlineIcon :size="44" />
			</template>
			{{ t('notes', 'Sharing and versions are not available right now.') }}
		</NcEmptyContent>
	</NcAppSidebar>
</template>

<script>
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { getSidebarTabs } from '@nextcloud/files'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import FileOutlineIcon from 'vue-material-design-icons/FileOutline.vue'
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import NoteInfo from './NoteInfo.vue'
import logger from '../Logger.js'
import { fetchNote } from '../NotesService.js'
import { selectNoteSidebarTabs } from '../sidebarTabs.js'
import store from '../store.js'
import { fetchDavNode } from '../WebdavService.js'

export default {
	name: 'NoteShareSidebar',

	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcEmptyContent,
		NcIconSvgWrapper,
		NcLoadingIcon,
		FileOutlineIcon,
		InformationOutlineIcon,
		NoteInfo,
	},

	data() {
		return {
			activeTab: 'sharing',
			contextError: '',
			contextRequestToken: 0,
			currentFolder: null,
			currentNode: null,
			initializingTabs: new Set(),
			initializedTabs: new Set(),
			isOpen: false,
			loadingContext: false,
			loadingContent: false,
			loadingTab: false,
			noteId: null,
			tabError: '',
		}
	},

	computed: {
		error() {
			return this.tabError || this.contextError
		},

		loading() {
			return this.loadingContext || this.loadingTab
		},

		note() {
			if (!Number.isFinite(this.noteId)) {
				return null
			}
			return store.notes.getNote(this.noteId)
		},

		tabs() {
			return selectNoteSidebarTabs(getSidebarTabs(), {
				node: this.currentNode,
				folder: this.currentFolder,
				view: this.currentView,
			})
		},

		currentView() {
			return {
				id: 'notes',
				name: this.t('notes', 'Notes'),
			}
		},
	},

	watch: {
		activeTab: 'ensureContent',
	},

	mounted() {
		// the share event is kept so anything already emitting it keeps working
		subscribe('notes:share:open', this.onShareOpen)
		subscribe('notes:sidebar:open', this.onSidebarOpen)
	},

	unmounted() {
		unsubscribe('notes:share:open', this.onShareOpen)
		unsubscribe('notes:sidebar:open', this.onSidebarOpen)
	},

	methods: {
		/**
		 * The note list payload excludes content, so a note that has never been
		 * opened has none. The info tab needs it for the counts — fetch it, but
		 * only when that tab is actually being looked at, so opening the sidebar
		 * to share a note does not pull its whole body down.
		 */
		async ensureContent() {
			if (this.activeTab !== 'notes-info' || this.loadingContent) {
				return
			}
			if (!Number.isFinite(this.noteId) || typeof this.note?.content === 'string') {
				return
			}

			this.loadingContent = true
			try {
				await fetchNote(this.noteId)
			} catch (error) {
				logger.error('Failed to load the note body for the info tab', { error })
			} finally {
				this.loadingContent = false
			}
		},

		async initializeTabs() {
			const tabs = this.tabs
			if (tabs.length === 0) {
				this.loadingTab = false
				this.tabError = this.t('notes', 'Sharing and versions are not available right now.')
				return
			}

			// One tab failing to define its element must not hide the others, so
			// they are initialised independently and only a total failure is
			// reported as an error.
			const results = await Promise.all(tabs.map((tab) => this.initializeTab(tab)))

			this.loadingTab = false
			this.tabError = results.includes(true)
				? ''
				: this.t('notes', 'Failed to load the note sidebar.')
		},

		/**
		 * @param {object} tab a registered Files sidebar tab
		 * @return {Promise<boolean>} whether the tab is usable
		 */
		async initializeTab(tab) {
			if (window.customElements.get(tab.tagName) || this.initializedTabs.has(tab.tagName)) {
				return true
			}

			if (this.initializingTabs.has(tab.tagName)) {
				// another open is already awaiting this one
				this.loadingTab = true
				return true
			}

			this.initializingTabs.add(tab.tagName)
			this.loadingTab = true

			try {
				await tab.onInit?.()
				await window.customElements.whenDefined(tab.tagName)
				this.initializedTabs.add(tab.tagName)
				return true
			} catch (error) {
				logger.error('Failed to initialize a sidebar tab in Notes', { error, tab: tab.id })
				return false
			} finally {
				this.initializingTabs.delete(tab.tagName)
			}
		},

		async loadNodeContext() {
			const internalPath = this.note?.internalPath
			if (!internalPath) {
				this.loadingContext = false
				this.currentNode = null
				this.currentFolder = null
				this.contextError = this.t('notes', 'Unable to load the selected note.')
				return
			}

			const requestToken = ++this.contextRequestToken
			this.loadingContext = true
			this.contextError = ''

			try {
				const node = await fetchDavNode(internalPath)
				let folder = null

				try {
					folder = await fetchDavNode(node.dirname || '/')
				} catch (error) {
					logger.error('Failed to load the parent folder for the Notes sidebar', { error })
				}

				if (requestToken !== this.contextRequestToken) {
					return
				}

				this.currentNode = node
				this.currentFolder = folder
			} catch (error) {
				if (requestToken !== this.contextRequestToken) {
					return
				}

				logger.error('Failed to load the selected note for the Notes sidebar', { error })
				this.currentNode = null
				this.currentFolder = null
				this.contextError = this.t('notes', 'Unable to load the selected note.')
			} finally {
				if (requestToken === this.contextRequestToken) {
					this.loadingContext = false
				}
			}
		},

		onShareOpen({ noteId }) {
			return this.onSidebarOpen({ noteId, tab: 'sharing' })
		},

		async onSidebarOpen({ noteId, tab = 'sharing' }) {
			this.contextRequestToken += 1
			this.noteId = Number(noteId)
			this.activeTab = tab
			this.isOpen = true
			this.contextError = ''
			this.tabError = ''
			this.currentNode = null
			this.currentFolder = null
			this.loadingContext = false
			this.loadingTab = false

			if (this.tabs.length === 0) {
				await this.initializeTabs()
				return
			}

			await Promise.all([
				this.initializeTabs(),
				this.loadNodeContext(),
				this.ensureContent(),
			])
		},

		onToggle(open) {
			if (!open) {
				this.isOpen = false
			}
		},

		onClosed() {
			if (this.isOpen) {
				return
			}

			this.contextRequestToken += 1
			this.noteId = null
			this.contextError = ''
			this.currentNode = null
			this.currentFolder = null
			this.loadingContext = false
			this.loadingTab = false
			this.tabError = ''
		},
	},
}
</script>
