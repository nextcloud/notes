<template>
	<div class="dashboard-box">
		<DashboardWidget :items="items" :loading="loading">
			<template #default="{ item }">
				<DashboardWidgetItem
					:target-url="getItemTargetUrl(item)"
					:main-text="item.title"
					:sub-text="subtext(item)"
				>
					<template #avatar>
						<div
							class="note-item"
							:class="{ 'note-item-favorite': item.favorite, 'note-item-no-favorites': !hasFavorites }"
						/>
					</template>
				</DashboardWidgetItem>
			</template>
			<template #empty-content>
				<EmptyContent icon="icon-notes">
					<template #desc>
						<p class="notes-empty-content-label">
							{{ t('notes', 'No notes yet') }}
						</p>
					</template>
				</EmptyContent>
			</template>
		</DashboardWidget>
		<div v-if="!loading" class="buttons-footer">
			<a :href="createNoteUrl" class="button">
				{{ t('notes', 'New note') }}
			</a>
		</div>
	</div>
</template>

<script>
import { DashboardWidget, DashboardWidgetItem, EmptyContent } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'

import { getDashboardData } from '../NotesService.js'
import { categoryLabel } from '../Util.js'

export default {
	name: 'Dashboard',

	components: {
		DashboardWidget,
		DashboardWidgetItem,
		EmptyContent,
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
	background: var(--icon-star-dark-FC0, var(--icon-star-dark-fc0));
}

.note-item {
	width: 44px;
	height: 44px;
	line-height: 44px;
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
	position: absolute;
	bottom: 1em;
	width: 100%;
	text-align: center;
}
</style>
