<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSidebar
		data-cy-notes-share-sidebar
		force-menu
		:active.sync="activeTab"
		:loading="isOpen && loading"
		:name="note?.title || t('notes', 'Share')"
		no-toggle
		:open="isOpen"
		@closed="onClosed"
		@update:open="onToggle"
	>
		<NcAppSidebarTab
			v-if="sharingTab"
			:id="sharingTab.id"
			:name="sharingTab.displayName"
			:order="sharingTab.order"
		>
			<template #icon>
				<NcIconSvgWrapper :svg="sharingTab.iconSvgInline" />
			</template>

			<NcEmptyContent v-if="loading">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>

			<NcEmptyContent v-else-if="!currentNode || error">
				<template #icon>
					<ShareVariantOutlineIcon :size="44" />
				</template>
				{{ error || t('notes', 'Unable to load the selected note for sharing.') }}
			</NcEmptyContent>

			<component
				:is="sharingTab.tagName"
				v-else
				:active.prop="activeTab === sharingTab.id"
				:folder.prop="currentFolder"
				:node.prop="currentNode"
				:view.prop="currentView"
			/>
		</NcAppSidebarTab>

		<NcEmptyContent v-else-if="isOpen">
			<template #icon>
				<ShareVariantOutlineIcon :size="44" />
			</template>
			{{ t('notes', 'Sharing is not available right now.') }}
		</NcEmptyContent>
	</NcAppSidebar>
</template>

<script>
import { getSidebarTabs } from '@nextcloud/files'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import ShareVariantOutlineIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
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
		ShareVariantOutlineIcon,
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
			return store.getters.getNote(this.noteId)
		},

		sharingTab() {
			return getSidebarTabs().find((tab) => tab.id === 'sharing') || null
		},

		currentView() {
			return {
				id: 'notes',
				name: this.t('notes', 'Notes'),
			}
		},
	},

	mounted() {
		subscribe('notes:share:open', this.onShareOpen)
	},

	destroyed() {
		unsubscribe('notes:share:open', this.onShareOpen)
	},

	methods: {
		async initializeSharingTab() {
			const tab = this.sharingTab
			if (!tab) {
				this.loadingTab = false
				this.tabError = this.t('notes', 'Sharing is not available right now.')
				return
			}

			if (window.customElements.get(tab.tagName) || this.initializedTabs.has(tab.tagName)) {
				this.loadingTab = false
				this.tabError = ''
				return
			}

			if (this.initializingTabs.has(tab.tagName)) {
				this.loadingTab = true
				return
			}

			this.initializingTabs.add(tab.tagName)
			this.loadingTab = true
			this.tabError = ''

			try {
				await tab.onInit?.()
				await window.customElements.whenDefined(tab.tagName)
				this.initializedTabs.add(tab.tagName)
			} catch (error) {
				console.error('Failed to initialize the sharing sidebar tab in Notes', error)
				this.tabError = this.t('notes', 'Failed to load the sharing sidebar.')
			} finally {
				this.initializingTabs.delete(tab.tagName)
				this.loadingTab = false
			}
		},

		async loadShareContext() {
			const internalPath = this.note?.internalPath
			if (!internalPath) {
				this.loadingContext = false
				this.currentNode = null
				this.currentFolder = null
				this.contextError = this.t('notes', 'Unable to load the selected note for sharing.')
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
					console.error('Failed to load the parent folder for the Notes sharing sidebar', error)
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

				console.error('Failed to load the selected note for the Notes sharing sidebar', error)
				this.currentNode = null
				this.currentFolder = null
				this.contextError = this.t('notes', 'Unable to load the selected note for sharing.')
			} finally {
				if (requestToken === this.contextRequestToken) {
					this.loadingContext = false
				}
			}
		},

		async onShareOpen({ noteId }) {
			this.contextRequestToken += 1
			this.noteId = Number(noteId)
			this.activeTab = 'sharing'
			this.isOpen = true
			this.contextError = ''
			this.tabError = ''
			this.currentNode = null
			this.currentFolder = null
			this.loadingContext = false
			this.loadingTab = false

			if (!this.sharingTab) {
				await this.initializeSharingTab()
				return
			}

			await Promise.all([
				this.initializeSharingTab(),
				this.loadShareContext(),
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
