<template>
	<div class="note-preview" v-html="html" />
</template>
<script>

import MarkdownIt from 'markdown-it'

export default {
	name: 'EditorMarkdownIt',

	props: {
		value: {
			type: String,
			required: true,
		},
	},

	data: function() {
		return {
			html: '',
			md: new MarkdownIt({
				linkify: true,
			}),
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
	padding: 0 1em;
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
}
</style>
