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
			disabled: false,
			liClass: 'task-list-item',
		})

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
		this.onUpdate()
	},

	methods: {
		onUpdate(markdown = this.value) {
			this.html = this.md.render(markdown)
			setTimeout(() => this.prepareOnChangeListener(), 100)
		},

		prepareOnChangeListener() {
			if (this.readonly) {
				return
			}
			const items = document.getElementsByClassName('task-list-item')
			for (let i = 0; i < items.length; ++i) {
				console.log(items[i])
				items[i].removeEventListener('click', this.setListener)
				items[i].addEventListener('click', this.setListener)
			}
		},

		setListener(item) {
			let idOfCheckbox = 0
			const markdownLines = this.value.split('\n')
			markdownLines.forEach((line, i) => {
				// Regex Source: https://github.com/linsir/markdown-it-task-checkbox/blob/master/index.js#L121
				// plus the '- '-string.
				if (/^(- )\[[xX \u00A0]\][ \u00A0]/.test(line.trim())) {
					markdownLines[i] = this.checkLine(line, i, idOfCheckbox, item.target)
					idOfCheckbox++
				}
			})
			this.updateMarkdown(markdownLines.join('\n'))
		},

		checkLine(line, index, id, target) {
			let returnValue = line;
			if ('cbx_' + id === target.id) {
				if (target.checked) {
					returnValue = returnValue.replace('[ ]', '[x]')
					returnValue = returnValue.replace('[\u00A0]', '[x]')
				} else {
					returnValue = returnValue.replace('[x]', '[ ]')
					returnValue = returnValue.replace('[X]', '[ ]')
				}
			}
			return returnValue
		},


		updateMarkdown(newMarkdown) {
			this.$emit('input', newMarkdown)
			this.onUpdate(newMarkdown)
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
				let path = token.attrs[aIndex][1]

				if (!path.startsWith('http')) {
					path = generateUrl('apps/notes/notes/{id}/attachment?path={path}', { id, path })
				}

				token.attrs[aIndex][1] = path
				const lowecasePath = path.toLowerCase()
				// pass token to default renderer.
				if (lowecasePath.endsWith('jpg')
					|| lowecasePath.endsWith('jpeg')
					|| lowecasePath.endsWith('bmp')
					|| lowecasePath.endsWith('webp')
					|| lowecasePath.endsWith('gif')
					|| lowecasePath.endsWith('png')) {
					return defaultRender(tokens, idx, options, env, self)
				} else {
					const dlimgpath = generateUrl('svg/core/actions/download?color=ffffff')
					return '<div class="download-file"><a href="' + path.replace(/"/g, '&quot;') + '"><div class="download-icon"><img class="download-icon-inner" src="' + dlimgpath + '">' + token.content + '</div></a></div>'
				}
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
		margin-left: 3ex;
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

	& pre, & code {
		background: var(--color-background-dark);
		font-size: 90%;
		padding: 0.2ex 0.5ex;
		white-space: pre-wrap;
	}

	& pre code {
		font-size: inherit;
		padding: 0;
	}

	& blockquote {
		font-style: italic;
		border-left: 4px solid var(--color-border);
		padding-left: 2ex;
		color: var(--color-text-light)
	}

	& table th {
		font-weight: bold;
	}

	& table td, & table th {
		padding-right: 1.5em;
	}

	& table td:empty::after {
		content: '\00a0';
	}

	.task-list-item {
		list-style-type: none;
		input {
			min-height: initial !important;
			cursor: pointer;
		}
		label {
			cursor: default;
		}
	}

	& img {
		width: 75%;
		margin-left: auto;
		margin-right: auto;
		display: block;
	}

	.download-file {
		width: 75%;
		margin-left: auto;
		margin-right: auto;
		display: block;
		text-align: center;
	}

	.download-icon {
		padding: 15px;
		margin-left: auto;
		margin-right: auto;
		width: 75%;
		border-radius: 10px;
		background-color: var(--color-background-dark);
		border: 1px solid transparent; // so that it does not move on hover
	}

	.download-icon:hover {
		border: 1px var(--color-primary-element) solid;
	}

	.download-icon-inner {
		height: 3em;
		width: auto;
		margin-bottom: 5px;
	}

}
</style>
