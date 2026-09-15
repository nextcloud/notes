<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSidebar
		v-model:active="activeTab"
		data-cy-notes-sidebar
		forceMenu
		:loading="isOpen && loading"
		:name="note?.title || t('notes', 'Note')"
		noToggle
		:open="isOpen"
		@closed="onClosed"
		@update:open="onToggle"
	>
		<template v-if="currentNode" #subname>
			<NoteSidebarSubname :node="currentNode" />
		</template>

		<NcAppSidebarTab v-for="tab in tabs"
			:id="tab.id"
			:key="tab.id"
			:name="tab.displayName"
			:order="tab.order"
		>
			<template #icon>
				<template v-if="tab.id === 'sharing'">
					<ShareVariantIcon v-if="resolvedTab === tab.id" :size="20" />
					<ShareVariantOutlineIcon v-else :size="20" />
				</template>
				<NcIconSvgWrapper v-else :svg="tab.iconSvgInline" inline />
			</template>

			<NcEmptyContent v-if="loading">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>

			<NcEmptyContent v-else-if="!currentNode || contextError">
				<template #icon>
					<FileOutlineIcon :size="44" />
				</template>
				{{ contextError || t('notes', 'Unable to load the selected note.') }}
			</NcEmptyContent>

			<component
				:is="tab.tagName"
				v-else
				:active.prop="resolvedTab === tab.id"
				:folder.prop="currentFolder"
				:node.prop="currentNode"
				:view.prop="currentView"
			/>
		</NcAppSidebarTab>

		<NcAppSidebarTab v-if="note"
			id="notes-info"
			:name="t('notes', 'Details')"
			:order="100"
		>
			<template #icon>
				<InformationIcon v-if="resolvedTab === 'notes-info'" :size="20" />
				<InformationOutlineIcon v-else :size="20" />
			</template>

			<!-- NcAppSidebarTab keeps an inactive tab mounted and hides it in CSS -->
			<NoteInfo
				v-if="resolvedTab === 'notes-info'"
				:note="note"
				:contentLoading="loadingContent"
				:contentError="contentError"
			/>
		</NcAppSidebarTab>

		<NcEmptyContent v-if="isOpen && tabIds.length === 0">
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
import InformationIcon from 'vue-material-design-icons/Information.vue'
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import ShareVariantIcon from 'vue-material-design-icons/ShareVariant.vue'
import ShareVariantOutlineIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import NoteInfo from './NoteInfo.vue'
import NoteSidebarSubname from './NoteSidebarSubname.vue'
import logger from '../Logger.js'
import { fetchNote } from '../NotesService.js'
import { initializeSidebarTab, selectNoteSidebarTabs } from '../sidebarTabs.js'
import store from '../store.js'
import { fetchDavNode } from '../WebdavService.js'

export default {
	name: 'NoteSidebar',

	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcEmptyContent,
		NcIconSvgWrapper,
		NcLoadingIcon,
		FileOutlineIcon,
		InformationIcon,
		InformationOutlineIcon,
		NoteInfo,
		NoteSidebarSubname,
		ShareVariantIcon,
		ShareVariantOutlineIcon,
	},

	data() {
		return {
			activeTab: 'sharing',
			contentError: false,
			contextError: '',
			contextRequestToken: 0,
			currentFolder: null,
			currentNode: null,
			failedTabs: new Set(),
			isOpen: false,
			loadingContentFor: null,
			loadingContext: false,
			loadingTab: false,
			noteId: null,
		}
	},

	computed: {
		loading() {
			return this.loadingContext || this.loadingTab
		},

		loadingContent() {
			return this.loadingContentFor !== null
		},

		note() {
			if (!Number.isFinite(this.noteId)) {
				return null
			}
			return store.notes.getNote(this.noteId)
		},

		availableTabs() {
			return selectNoteSidebarTabs(getSidebarTabs(), {
				node: this.currentNode,
				folder: this.currentFolder,
				view: this.currentView,
			})
		},

		tabs() {
			return this.availableTabs.filter((tab) => !this.failedTabs.has(tab.tagName))
		},

		/** Ids of the tabs the sidebar renders, in the order they appear */
		tabIds() {
			return [
				...this.tabs.map(({ id }) => id),
				...(this.note ? ['notes-info'] : []),
			]
		},

		/**
		 * NcAppSidebar falls back to its first tab when the active one is not
		 * among them, but does not report that back, so the tab id has to be
		 * clamped here as well for `active` to reach the right custom element.
		 * The watcher below writes the clamped id back, so `activeTab` does not
		 * keep naming a tab that is not on screen.
		 */
		resolvedTab() {
			if (this.tabIds.includes(this.activeTab)) {
				return this.activeTab
			}
			return this.tabIds[0] ?? this.activeTab
		},

		routeNoteId() {
			const noteId = Number(this.$route?.params?.noteId)
			return Number.isFinite(noteId) ? noteId : null
		},

		currentView() {
			return {
				id: 'notes',
				name: this.t('notes', 'Notes'),
			}
		},
	},

	watch: {
		resolvedTab(tabId) {
			if (tabId !== this.activeTab) {
				this.activeTab = tabId
			}
			this.ensureContent()
		},

		/**
		 * @param {number|null} noteId the note the route moved to
		 */
		routeNoteId(noteId) {
			if (this.isOpen && noteId !== null && noteId !== this.noteId) {
				this.onSidebarOpen({ noteId, tab: this.resolvedTab })
			}
		},
	},

	mounted() {
		// notes:share:open is the legacy name of notes:sidebar:open and opens
		// the sharing tab; it stays so anything still emitting it keeps working
		subscribe('notes:share:open', this.onShareOpen)
		subscribe('notes:sidebar:open', this.onSidebarOpen)
		subscribe('files:node:updated', this.onNodeUpdated)
	},

	unmounted() {
		unsubscribe('notes:share:open', this.onShareOpen)
		unsubscribe('notes:sidebar:open', this.onSidebarOpen)
		unsubscribe('files:node:updated', this.onNodeUpdated)
	},

	methods: {
		async initializeTabs() {
			const tabs = this.availableTabs
			if (tabs.length === 0) {
				this.loadingTab = false
				return
			}

			const requestToken = this.contextRequestToken
			this.loadingTab = true

			const results = await Promise.all(tabs.map(initializeSidebarTab))

			if (requestToken !== this.contextRequestToken) {
				return
			}

			tabs.forEach((tab, index) => {
				if (!results[index]) {
					this.failedTabs.add(tab.tagName)
				}
			})

			this.loadingTab = false
		},

		/**
		 * The note list payload excludes content, so a note that has never been
		 * opened has none client-side and the reading estimate has to fetch one.
		 */
		async ensureContent() {
			if (this.resolvedTab !== 'notes-info') {
				return
			}
			const noteId = this.noteId
			if (!Number.isFinite(noteId) || typeof this.note?.content === 'string') {
				return
			}
			if (this.loadingContentFor === noteId) {
				return
			}

			this.loadingContentFor = noteId
			this.contentError = false
			try {
				// fetchNote() only rejects on a missing note and reports anything
				// else itself, so the content is what says whether it worked
				const note = await fetchNote(noteId)
				if (this.loadingContentFor !== noteId) {
					return
				}
				this.contentError = typeof note?.content !== 'string'
			} catch (error) {
				if (this.loadingContentFor !== noteId) {
					return
				}
				logger.error('Failed to load the note body for the Details tab', { error })
				this.contentError = true
			} finally {
				if (this.loadingContentFor === noteId) {
					this.loadingContentFor = null
				}
			}
		},

		resetContext() {
			this.contextRequestToken += 1
			this.contentError = false
			this.contextError = ''
			this.currentNode = null
			this.currentFolder = null
			this.loadingContentFor = null
			this.loadingContext = false
			this.loadingTab = false
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

			const requestToken = this.contextRequestToken
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

		/**
		 * Tabs report what they changed about the note through this event — a
		 * restored version for instance — and hand out a node of their own,
		 * which they in turn watch for changes.
		 *
		 * @param {object} node the updated node
		 */
		onNodeUpdated(node) {
			if (node?.source && node.source === this.currentNode?.source) {
				this.currentNode = node
			}
		},

		onShareOpen({ noteId }) {
			return this.onSidebarOpen({ noteId, tab: 'sharing' })
		},

		async onSidebarOpen({ noteId, tab = 'sharing' }) {
			this.resetContext()
			this.noteId = Number(noteId)
			this.isOpen = true
			this.failedTabs.clear()
			this.activeTab = tab

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

			this.resetContext()
			this.noteId = null
		},
	},
}
</script>
