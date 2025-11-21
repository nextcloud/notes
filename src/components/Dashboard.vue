<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="dashboard-box">
		<NcDashboardWidget
			empty-content-icon="icon-notes"
			:empty-content-message="t('notes', 'No notes yet')"
			:items="items"
			:loading="loading"
		>
			<template #default="{ item }">
				<NcDashboardWidgetItem
					:target-url="getItemTargetUrl(item)"
					:main-text="item.title"
					:sub-text="subtext(item)"
				>
					<div slot="avatar"
						class="note-item"
						:class="{ 'note-item-favorite': item.favorite, 'note-item-no-favorites': !hasFavorites }"
					/>
				</NcDashboardWidgetItem>
			</template>
		</NcDashboardWidget>
		<div v-if="!loading" class="buttons-footer">
			<NcButton :href="createNoteUrl">
				<Plus slot="icon" :size="20" />
				{{ t('notes', 'New note') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDashboardWidget from '@nextcloud/vue/components/NcDashboardWidget'
import NcDashboardWidgetItem from '@nextcloud/vue/components/NcDashboardWidgetItem'
import { generateUrl } from '@nextcloud/router'

import Plus from 'vue-material-design-icons/Plus.vue'

import { getDashboardData } from '../NotesService.js'
import { categoryLabel } from '../Util.js'

export default {
	name: 'Dashboard',

	components: {
		NcButton,
		NcDashboardWidget,
		NcDashboardWidgetItem,
		Plus,
	},

	data() {
		return {
			loading: true,
			items: [],
			hasMoreItems: false,
		}
	},

	computed: {
		showMoreUrl() {
			return this.hasMoreItems ? generateUrl('/apps/notes') : null
		},

		hasFavorites() {
			return this.items.length > 0 && this.items[0].favorite
		},

		createNoteUrl() {
			return generateUrl('/apps/notes/new')
		},

		getItemTargetUrl() {
			return (note) => {
				return generateUrl(`/apps/notes/note/${note.id}`)
			}
		},
	},

	created() {
		this.loadDashboardData()
	},

	methods: {
		loadDashboardData() {
			getDashboardData().then(data => {
				this.items = data.items
				this.hasMoreItems = data.hasMoreItems
				this.loading = false
			})
		},

		subtext(item) {
			return item.excerpt || categoryLabel(item.category)
		},
	},
}
</script>

<style scoped>
.dashboard-box {
	position: relative;
	height: 100%;
}

.note-item-favorite {
	background: var(--icon-starred-yellow);
}

.note-item {
	width: var(--default-clickable-area);
	height: var(--default-clickable-area);
	line-height: var(--default-clickable-area);
	flex-shrink: 0;
	background-size: 50%;
	background-repeat: no-repeat;
	background-position: center;
}

.note-item-no-favorites {
	display: none;
}

.notes-empty-content-label {
	margin-bottom: 20px;
}

.buttons-footer {
	display: flex;
	align-items: center;
	justify-content: center;
	margin-top: 8px;
}
</style>
