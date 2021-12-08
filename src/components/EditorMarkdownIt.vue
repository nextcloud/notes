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
		}
	},

	data() {

		const md = new MarkdownIt({
			linkify: true,
		})

		md.use(require('markdown-it-task-checkbox'), {
			disabled: true,
			liClass: 'task-list-item',
		})

		const markdown = new MarkdownIt({
			linkify: true,
		})

		return {
			html: '',
			md,
			md: markdown
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
				let path = token.attrs[aIndex][1]

				if (!path.startsWith('http')) {
					path = generateUrl('apps/notes') + '/notes/' + id + '/attachment?path=' + path
				}

				token.attrs[aIndex][1] = path
				// pass token to default renderer.
				if (path.endsWith("jpg") ||
					path.endsWith("jpeg") ||
					path.endsWith("bmp") ||
					path.endsWith("webp") ||
					path.endsWith("gif") ||
					path.endsWith("png")) {
					return defaultRender(tokens, idx, options, env, self)
				}else{
					let dlimgpath = generateUrl('svg/core/actions/download?color=ffffff');
					return "<div class='download-file'><a href='"+path+"'><div class='download-icon'><img class='download-icon-inner' src='"+dlimgpath+"'>"+token.content+"</div></a></div>"
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
