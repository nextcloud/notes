<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="conflict-solution">
		<div class="text">
			<pre v-for="(l, i) in diff" :key="i" :class="l.className">{{ l.line }}</pre>
		</div>
		<button @click="$emit('on-choose-solution')">
			{{ button }}
		</button>
	</div>
</template>
<script>

import { diffLines } from 'diff'

export default {
	name: 'ConflictSolution',

	props: {
		content: {
			type: String,
			required: true,
		},
		reference: {
			type: String,
			required: true,
		},
		button: {
			type: String,
			required: true,
		},
	},

	computed: {
		diff() {
			const diffs = diffLines(this.reference, this.content)
			const line2class = function(line) {
				if (line.added) {
					return 'added'
				} else if (line.removed) {
					return 'removed'
				} else {
					return 'unchanged'
				}
			}
			const lines = []
			diffs.forEach(diff => {
				const className = line2class(diff)
				diff.value.replace(/\r?\n$/, '').split(/\r?\n/).forEach(line => {
					lines.push({ line, className })
				})
			})
			return lines
		},
	},
}
</script>
<style scoped>
.conflict-solution {
	height: 100%;
	padding: 1ex;
	margin: 1ex;
	flex: 1;
}

.conflict-solution .text {
	max-height: 60vh;
	overflow: auto;
	background-color: var(--color-background-darker);
	padding: 0 1ex;
}

.conflict-solution .text pre {
	white-space: pre-wrap;
	line-height: 1.2;
	padding: 0.3ex 0.5ex;
}

.conflict-solution .text .removed {
	background-color: rgba(128, 128, 128, 0.2);
	color: rgba(128, 128, 128, 1);
	text-decoration: line-through;
}

.conflict-solution .text .added {
	background-color: rgba(70, 186, 97, 0.2);
	color: rgba(70, 186, 97, 1);
}

.conflict-solution button {
	margin: auto;
	margin-top: 2ex;
	display: block;
}
</style>
