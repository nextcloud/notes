<template>
	<div class="markdown-editor">
		<textarea />
	</div>
</template>
<script>

import EasyMDE from 'easymde'

export default {
	name: 'TheEditor',

	props: {
		value: {
			type: String,
			required: true,
		},
	},

	data: function() {
		return {
			config: {
				spellChecker: false,
				autoDownloadFontAwesome: false,
				toolbar: false,
				status: false,
				forceSync: true,
			},
			mde: null,
		}
	},

	watch: {
		value(val) {
			if (val !== this.mde.value()) {
				this.mde.value(val)
			}
		},
	},

	mounted() {
		this.initialize()
	},

	destroyed() {
		this.mde = null
	},

	methods: {
		initialize() {
			const config = Object.assign({
				element: this.$el.firstElementChild,
				initialValue: this.value,
				renderingConfig: {},
			}, this.config)

			this.mde = new EasyMDE(config)

			this.mde.codemirror.on('change', () => {
				this.$emit('input', this.mde.value())
			})
			this.initializeCheckboxes()
		},

		initializeCheckboxes() {
			/* Initialize Checkboxes */
			let editor = this
			// TODO move the following from jQuery to plain JS
			$('.CodeMirror-code').on('mousedown.checkbox touchstart.checkbox', '.cm-formatting-task', function(e) {
				e.preventDefault()
				e.stopImmediatePropagation()
				editor.toggleCheckbox(e.target)
			})
		},

		toggleCheckbox(el) {
			// TODO move the following from jQuery to plain JS
			let $el = $(el)
			let doc = this.mde.codemirror.getDoc()
			let index = $el.parents('.CodeMirror-line').index()
			let line = doc.getLineHandle(index)

			let newvalue = ($el.text() === '[x]') ? '[ ]' : '[x]'

			// + 1 for some reason... not sure why
			doc.replaceRange(newvalue,
				{ line: index, ch: line.text.indexOf('[') },
				{ line: index, ch: line.text.indexOf(']') + 1 }
			)
		},

	},
}
</script>
<style>
@import '~easymde/dist/easymde.min.css';

.markdown-editor {
	min-height: 100%;
	padding: 1em;
}

.CodeMirror {
	min-height: 100%;
	max-width: 47em;
	font-size: 16px;
	line-height: 1.5em;
	border: none;
}

/* Markdown markup */
.CodeMirror .cm-formatting {
	opacity: 0.3;
}

.CodeMirror .cm-formatting-task,
.CodeMirror .cm-formatting-list {
	opacity: inherit;
}

/* Headlines */
.cm-s-easymde .cm-header-1 {
	font-size: 165%;
}

.cm-s-easymde .cm-header-2 {
	font-size: 140%;
}

.cm-s-easymde .cm-header-3 {
	font-size: 120%;
}

.cm-s-easymde .cm-header-4 {
	font-size: 110%;
}

/* horizontal line */
.CodeMirror .cm-hr {
	display: inline-block;
	width: 100%;
	line-height: 0.25;
	background-color: #ccc;
}

/* Code */
.CodeMirror .cm-comment {
	font-family: MONOSPACE;
	font-size: 90%;
}

/* Quotes */
.cm-s-easymde .cm-quote {
	color: inherit;
}

/* Checkboxes */
.CodeMirror .cm-formatting-task {
	position: relative;
	display: inline-block;
	width: 1.8em;
}

.CodeMirror .cm-formatting-task.cm-meta::before {
	content: '\2610';
	font-size: 1.5em;
	background: white;
	position: absolute;
}

.CodeMirror .cm-formatting-task.cm-property::before {
	content: '\2611';
	font-size: 1.5em;
	background: white;
	position: absolute;
}

.CodeMirro .cm-formatting-task.cm-property + span {
	opacity: 0.5;
	text-decoration: line-through;
}
</style>
