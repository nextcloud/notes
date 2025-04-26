<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- eslint-disable-next-line vue/no-v-html -->
	<div class="note-preview" v-html="html" />
</template>
<script>

import MarkdownIt from 'markdown-it'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'EditorMarkdownIt',

	props: {
		value: {
			type: String,
			required: true,
		},
		readonly: {
			type: Boolean,
			required: true,
		},
		noteid: {
			type: String,
			required: true,
		},
	},

	data() {

		const md = new MarkdownIt({
			linkify: true,
			breaks: true,
		})

		md.use(require('markdown-it-task-checkbox'), {
			disabled: this.readonly,
			liClass: 'task-list-item',
		})

		md.use(require('markdown-it-bidi'))

		return {
			html: '',
			md,
		}
	},

	watch: {
		value: 'onUpdate',
	},

	created() {
		this.setImageRule(this.noteid)

		this.setInlineCodeRule()
		this.onUpdate()
	},

	methods: {
		onUpdate() {
			this.html = this.md.render(this.value)
			if (!this.readonly) {
				setTimeout(() => this.prepareOnClickListener(), 100)
			}
		},

		prepareOnClickListener() {
			const items = document.getElementsByClassName('task-list-item')
			for (let i = 0; i < items.length; ++i) {
				items[i].removeEventListener('click', this.onClickListItem)
				items[i].addEventListener('click', this.onClickListItem)
			}
		},

		onClickListItem(event) {
			event.stopPropagation()
			let idOfCheckbox = 0
			const markdownLines = this.value.split('\n')
			markdownLines.forEach((line, i) => {
				// Regex Source: https://github.com/linsir/markdown-it-task-checkbox/blob/master/index.js#L121
				// plus the '- '-string.
				if (/^[-+*]\s+\[[xX \u00A0]\][ \u00A0]/.test(line.trim())) {
					markdownLines[i] = this.checkLine(line, i, idOfCheckbox, event.target)
					idOfCheckbox++
				}
			})

			this.$emit('input', markdownLines.join('\n'))
		},

		checkLine(line, index, id, target) {
			let returnValue = line
			if ('cbx_' + id === target.id) {
				if (target.checked) {
					returnValue = returnValue.replace(/\[[ \u00A0]\]/, '[x]')
				} else {
					// matches [x] or [X], to prevent two occurences of uppercase and lowercase X to be replaced
					returnValue = returnValue.replace(/\[[xX]\]/, '[ ]')
				}
			}
			return returnValue
		},

		setImageRule(id) {
			// https://github.com/markdown-it/markdown-it/blob/master/docs/architecture.md#renderer
			// Remember old renderer, if overridden, or proxy to default renderer
			const defaultRender = this.md.renderer.rules.image || function(tokens, idx, options, env, self) {
				return self.renderToken(tokens, idx, options)
			}

			this.md.renderer.rules.image = function(tokens, idx, options, env, self) {
				// If you are sure other plugins can't add `target` - drop check below
				const token = tokens[idx]
				const aIndex = token.attrIndex('src')
				let download = false
				let path = token.attrs[aIndex][1]

				if (!path.startsWith('http://')
					&& !path.startsWith('https://')
					&& !path.startsWith('data:')
				) {
					path = path.split('?').shift()
					const lowecasePath = path.toLowerCase()
					path = generateUrl(
						'apps/notes/notes/{id}/attachment?path={path}',
						{ id, path: decodeURIComponent(path) },
					)
					token.attrs[aIndex][1] = path

					if (!lowecasePath.endsWith('.jpg')
						&& !lowecasePath.endsWith('.jpeg')
						&& !lowecasePath.endsWith('.bmp')
						&& !lowecasePath.endsWith('.webp')
						&& !lowecasePath.endsWith('.gif')
						&& !lowecasePath.endsWith('.png')
					) {
						download = true
					}
				}

				if (download) {
					const dlimgpath = generateUrl('svg/core/actions/download?color=ffffff')
					return '<div class="download-file"><a href="' + path.replace(/"/g, '&quot;') + '"><div class="download-icon"><img class="download-icon-inner" src="' + dlimgpath + '">' + token.content + '</div></a></div>'
				} else {
					// pass token to default renderer.
					return defaultRender(tokens, idx, options, env, self)
				}
			}
		},

		setInlineCodeRule() {
			this.md.renderer.rules.code_inline = function(tokens, idx, options, env, self) {
				const token = tokens[idx]
				return '<code class="inline-code">' + token.content + '</code>'
			}
		},
	},

}
</script>
<style lang="scss">
.note-preview {
	padding: 1em;
	padding-top: 0;
	line-height: 1.5em;
	word-wrap: break-word;

	& h1, & h2, & h3, & h4, & h5, & h6 {
		padding: 0;
		margin-top: 2ex;
		margin-bottom: 1ex;
		font-weight: bold;
		color: inherit;
	}

	& h1 {
		font-size: 165%;
	}

	& h2 {
		font-size: 140%;
	}

	& h3 {
		font-size: 120%;
	}

	& h4 {
		font-size: 110%;
	}

	& p, & pre, & ul, & ol {
		margin: 2ex 0;
	}

	& ul {
		list-style: initial;
	}

	& ul, & ol {
		margin-inline-start: 3ex;
	}

	& li > p, & li > ul, & li > ol {
		margin-top: 0.5ex;
		margin-bottom: 0.5ex;
	}

	& em {
		font-style: italic;
		color: inherit;
	}

	& a {
		color: var(--color-primary-element);
	}

	& pre code {
		font-size: inherit;
		padding: 0;
	}

	& blockquote {
		font-style: italic;
		border-inline-start: 4px solid var(--color-border);
		padding-inline-start: 2ex;
		color: var(--color-text-light)
	}

	.task-list-item {
		list-style-type: none;
		input {
			min-height: initial !important;
			height: auto !important;
			cursor: pointer;
		}
		label {
			cursor: default;
		}
	}

	& img {
		width: 75%;
		display: block;
	}

	.download-file {
		width: 75%;
		display: block;
		text-align: center;
	}

	.download-icon {
		padding: 15px;
		width: 75%;
		border-radius: 10px;
		background-color: var(--color-background-dark);
		border: 1px solid transparent; // so that it does not move on hover
	}

	.download-icon:hover {
		border-color: var(--color-primary-element);
	}

	.download-icon-inner {
		height: 3em;
		width: auto;
		margin-inline-start: auto;
		margin-inline-end: auto;
		margin-bottom: 5px;
	}

	& table {
		width: calc(100% - 50px);
		table-layout: auto;
		margin-top: 2em;
		margin-bottom: 2em;
		border-radius: 0.5em;
		border-collapse: collapse;
		border-style: hidden;
		box-shadow: 0 0 0 1px var(--color-border);
	}

	& table td, & table th {
		padding: 0.35em 0.5em;
		text-align: start;
		border: 1px solid var(--color-border);
	}

	& table tr:hover {
		background-color: var(--color-primary-element-lighter);
	}

	& table th {
		font-weight: bold;
	}

	& table td:empty::after {
		content: '\00a0';
	}

	pre {
		border-radius: 10px;
		padding: 15px;
		background: var(--color-background-dark);
		font-size: 90%;
		white-space: pre-wrap;
	}

	.inline-code {
		border-radius: 8px;
		padding: 3px 8px;
		background: var(--color-background-dark);
		font-size: 85%;
	}
}
</style>
