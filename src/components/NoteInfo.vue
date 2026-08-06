<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<dl class="note-info">
		<div v-for="row in rows" :key="row.label" class="note-info__row">
			<dt class="note-info__label">
				{{ row.label }}
			</dt>
			<dd class="note-info__value" :title="row.title || undefined">
				{{ row.value }}
			</dd>
		</div>
	</dl>
</template>

<script>
import { formatFileSize } from '@nextcloud/files'
import moment from '@nextcloud/moment'
import { noteTextStats } from '../noteStats.js'
import { categoryLabel } from '../Util.js'

export default {
	name: 'NoteInfo',

	props: {
		/** The note, as held in the store */
		note: {
			type: Object,
			required: true,
		},

		/** The note's DAV node, or null while it loads */
		node: {
			type: Object,
			default: null,
		},

		/** Whether the note's content has been loaded yet */
		contentLoading: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		stats() {
			return noteTextStats(this.note.content)
		},

		hasContent() {
			return typeof this.note.content === 'string'
		},

		rows() {
			const rows = [
				{
					label: t('notes', 'Category'),
					value: this.note.category
						? categoryLabel(this.note.category)
						: t('notes', 'Uncategorized'),
				},
			]

			// the counts need the body, which is fetched separately
			if (this.contentLoading && !this.hasContent) {
				rows.push({ label: t('notes', 'Words'), value: '…' })
			} else if (this.hasContent) {
				rows.push(
					{ label: t('notes', 'Words'), value: this.formatNumber(this.stats.words) },
					{ label: t('notes', 'Characters'), value: this.formatNumber(this.stats.characters) },
					{
						label: t('notes', 'Reading time'),
						value: this.stats.readingMinutes === 0
							? '—'
							: n('notes', '%n minute', '%n minutes', this.stats.readingMinutes),
					},
				)
			}

			if (this.node?.size !== undefined && this.node.size !== null) {
				rows.push({ label: t('notes', 'Size'), value: formatFileSize(this.node.size) })
			}

			if (this.node?.crtime) {
				rows.push(this.dateRow(t('notes', 'Created'), this.node.crtime))
			}
			if (this.note.modified) {
				// the API reports seconds, moment expects milliseconds
				rows.push(this.dateRow(t('notes', 'Modified'), this.note.modified * 1000))
			}

			rows.push({
				label: t('notes', 'Path'),
				value: this.note.internalPath || '—',
				title: this.note.internalPath,
			})

			if (this.note.readonly) {
				rows.push({ label: t('notes', 'Access'), value: t('notes', 'Read-only') })
			}

			return rows
		},
	},

	methods: {
		/**
		 * @param {string} label row label
		 * @param {number|Date} value a timestamp in milliseconds
		 * @return {object} row showing "3 days ago" with the exact date on hover
		 */
		dateRow(label, value) {
			return {
				label,
				value: moment(value).fromNow(),
				title: moment(value).format('LLLL'),
			}
		},

		formatNumber(value) {
			return value.toLocaleString(OC.getLanguage?.() || undefined)
		},
	},
}
</script>

<style lang="scss" scoped>
.note-info {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	margin: 0;
	padding: calc(var(--default-grid-baseline) * 2) 0;
}

.note-info__row {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	align-items: baseline;
}

.note-info__label {
	flex: 0 0 40%;
	color: var(--color-text-maxcontrast);
}

.note-info__value {
	flex: 1 1 auto;
	margin: 0;
	/* a long path must wrap rather than widen the sidebar */
	overflow-wrap: anywhere;
}
</style>
