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
			disabled: true,
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
		onUpdate() {
			this.html = this.md.render(this.value)
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

	.task-list-item {
		list-style-type: none;
		input {
			min-height: initial !important;
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
		margin-left: auto;
		margin-right: auto;
		margin-bottom: 5px;
	}

	& table {
		width: calc(100% - 50px);
		table-layout: auto;
		margin-top: 2em;
		margin-bottom: 2em;
		border-radius: 7px;
		border-collapse: collapse;
		border-style: hidden;
		box-shadow: 0 0 0 1px var(--color-border);
	}

	& table th {
		padding-top: 12px;
		padding-bottom: 12px;
		text-align: left;
	}

	& table td, & table th {
		padding: 8px;
		padding-right: 1.5em;
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
}
</style>
