<template>
	<div>
		<DashboardWidget
			:items="items"
			:loading="loading"
			:showMoreUrl="showMoreUrl"
		>
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
		<div class="buttons-footer" :style="buttonsFooterStyle">
			<a :href="createNoteUrl" class="button">
				{{ t('notes', 'Create a new note') }}
			</a>
		</div>
	</div>
</template>

<script>
import { DashboardWidget, DashboardWidgetItem } from '@nextcloud/vue-dashboard' // TODO: should be refactored with next release of @nextcloud/vue : https://github.com/nextcloud/nextcloud-vue-dashboard/issues/407
import { EmptyContent } from '@nextcloud/vue'
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
			creating: false,
			items: [],
			hasMoreItems: false,
			displayedItemsCount: 6,
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

		buttonsFooterStyle() {
			const marginTop = this.items.length > 0 ? 20 + (this.displayedItemsCount - this.items.length) * 60 : 10
			return { marginTop: `${marginTop}px` }
		}
	},

	created() {
		this.loadDashboardData()
	},

	methods: {
		loadDashboardData() {
			getDashboardData().then(data => {
				this.items = data.items
				this.hasMoreItems = data.hasMoreItems
				this.displayedItemsCount = data.displayedItemsCount
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
	text-align: center;
}
</style>
