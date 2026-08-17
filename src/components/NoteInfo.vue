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

		/** Whether the note's content has been loaded yet */
		contentLoading: {
			type: Boolean,
			default: false,
		},

		/** Whether loading the note's content failed */
		contentError: {
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
					label: this.t('notes', 'Category'),
					value: this.note.category
						? categoryLabel(this.note.category)
						: this.t('notes', 'Uncategorized'),
				},
			]

			// the reading estimate needs the body, which is fetched separately
			if (this.contentLoading && !this.hasContent) {
				rows.push({ label: this.t('notes', 'Reading time'), value: '…' })
			} else if (this.hasContent) {
				rows.push({
					label: this.t('notes', 'Reading time'),
					value: this.stats.readingMinutes === 0
						? '—'
						: this.n('notes', '%n minute', '%n minutes', this.stats.readingMinutes),
				})
			} else if (this.contentError) {
				rows.push({
					label: this.t('notes', 'Reading time'),
					value: '—',
					title: this.t('notes', 'The note content could not be loaded.'),
				})
			}

			if (this.note.readonly) {
				rows.push({ label: this.t('notes', 'Access'), value: this.t('notes', 'Read-only') })
			}

			rows.push({
				label: this.t('notes', 'Path'),
				value: this.note.internalPath || '—',
			})

			return rows
		},
	},
}
</script>

<style lang="scss" scoped>
.note-info {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 3);
	margin: 0;
	/* the inset the Files sidebar tabs put their own content at */
	padding: calc(var(--default-grid-baseline) * 2);
}

.note-info__row {
	display: flex;
	flex-direction: column;
}

.note-info__label,
.note-info__value {
	padding: 0;
	white-space: normal;
}

.note-info__label {
	width: auto;
	text-align: start;
	color: var(--color-text-maxcontrast);
}

.note-info__value {
	margin: 0;
	font-variant-numeric: tabular-nums;
	/* a long path must wrap rather than widen the sidebar */
	overflow-wrap: anywhere;
}
</style>
