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
	},

	data() {
		const markdown = new MarkdownIt({
				linkify: true,
			});


		// https://github.com/markdown-it/markdown-it/blob/master/docs/architecture.md#renderer
		// Remember old renderer, if overridden, or proxy to default renderer
		var defaultRender = markdown.renderer.rules.image || function(tokens, idx, options, env, self) {
			return self.renderToken(tokens, idx, options);
		};

		markdown.renderer.rules.image = function (tokens, idx, options, env, self) {
			// If you are sure other plugins can't add `target` - drop check below
			var aIndex = tokens[idx].attrIndex('src');
			var source = tokens[idx].attrs[aIndex][1];

			//rewrite dots to ; for url-encoding. See the corresponding API
			source = source.replace("../", ";;/");

			var id = "199"
			if(!source.startsWith("http")) {
				source = generateUrl('apps/notes') + "/notes/image/" + id + "/" + source;
			}

			tokens[idx].attrs[aIndex][1] = source
			// pass token to default renderer.
			return defaultRender(tokens, idx, options, env, self);
		};

		md.use(require('markdown-it-task-checkbox'), {
			disabled: true,
			liClass: 'task-list-item',
		})

		return {
			html: '',
			md: markdown
		}
	},

	watch: {
		value: 'onUpdate',
	},

	created() {
		this.onUpdate()
	},

	methods: {
		onUpdate() {
			this.html = this.md.render(this.value)
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

	& img {
		width: 75%;
		margin-left: auto;
		margin-right: auto;
		display: block;
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
}
</style>
