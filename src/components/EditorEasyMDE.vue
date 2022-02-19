<template>
	<div>
		<div class="toolbar">
			<a class="button" :title="t('notes', 'Undo')" @click="undo">
				<span class=""></span>
				<span class="">{{ t('notes', 'u') }}</span>
			</a>
			<a class="button" :title="t('notes', 'Redo')" @click="redo">
				<span class=""></span>
				<span class="">{{ t('notes', 'r') }}</span>
			</a>
			<a class="button" :title="t('notes', 'Bold')" @click="makeBold">
				<span class=""></span>
				<span class="">{{ t('notes', 'B') }}</span>
			</a>
			<a class="button" :title="t('notes', 'Italic')" @click="makeItalic">
				<span class=""></span>
				<span class="">{{ t('notes', 'i') }}</span>
			</a>
			<a class="button" :title="t('notes', 'Strikethrough')" @click="makeStrikethrough">
				<span class=""></span>
				<span class="">{{ t('notes', '-S-') }}</span>
			</a>
			<a class="button" :title="t('notes', 'Title')" @click="makeMonospace">
				<span class=""></span>
				<span class="">{{ t('notes', 'H1') }}</span>
			</a>
			<a class="button" :title="t('notes', 'Insert Link')" @click="insertLink">
				<span class="icon-category-organization"></span>
				<span class="toolbar_label">{{ t('notes', 'Insert Link') }}</span>
			</a>
			<a class="button" :title="t('notes', 'Insert Checkbox')" @click="insertCheckbox">
				<span class="icon-category-organization"></span>
				<span class="toolbar_label">{{ t('notes', 'Insert Checkbox') }}</span>
			</a>
			<a class="button" :title="t('notes', 'Monospace')" @click="makeMonospace">
				<span class=""></span>
				<span class="">{{ t('notes', '<>') }}</span>
			</a>
			<a class="button" @click="onClickUpload" :title="t('notes', 'Upload Image')">
				<span class="icon-picture"></span>
				<span></span>
			</a>
		</div>
		<div class="markdown-editor" @click="onClickEditor">
			<textarea />
		</div>
	</div>
</template>
<script>

import EasyMDE from 'easymde'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import store from '../store'

export default {
	name: 'EditorEasyMDE',

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
		return {
			config: {
				spellChecker: false,
				nativeSpellcheck: true,
				inputStyle: 'contenteditable',
				autoDownloadFontAwesome: false,
				toolbar: false,
				status: false,
				forceSync: true,
				tabSize: 4,
			},
			mde: null,
		}
	},

	watch: {
		value(val) {
			if (val !== this.mde.value()) {
				const position = this.mde.codemirror.getCursor()
				this.mde.value(val)
				this.mde.codemirror.setCursor(position)
			}
		},
	},

	mounted() {
		this.initialize()
		this.mde.codemirror.focus()
		this.$emit('add-menu-item', t('notes', 'Make Italic'), 'icon-toggle', this.makeItalic, 'editor', false)
	},

	destroyed() {
		this.mde = null
	},

	methods: {
		initialize() {
			const config = Object.assign({
				element: this.$el.lastElementChild.firstElementChild,
				initialValue: this.value,
				renderingConfig: {},
			}, this.config)

			this.mde = new EasyMDE(config)

			// keys Home/End should apply to the visual line
			this.mde.codemirror.addKeyMap({
				Home: 'goLineLeft',
				End: 'goLineRight',
			})

			// pass event for changes
			this.mde.codemirror.on('change', () => {
				this.$emit('input', this.mde.value())
			})

			// listen for click/touch events in order to toggle checkboxes
			document.querySelectorAll('.CodeMirror-code').forEach((codeElement) => {
				codeElement.addEventListener('mousedown', this.onClickCodeElement)
				codeElement.addEventListener('touchstart', this.onClickCodeElement)
			})

			if (this.readonly) {
				this.mde.codemirror.options.readOnly = true
			}
		},

		onClickCodeElement(event) {
			const element = event.target.closest('.cm-formatting-task')
			if (element !== null) {
				event.preventDefault()
				event.stopImmediatePropagation()
				this.toggleCheckbox(event.target)
			}
		},

		toggleCheckbox(el) {
			const doc = this.mde.codemirror.getDoc()
			const domLine = el.closest('.CodeMirror-line')
			const index = [].indexOf.call(domLine.parentElement.children, domLine)
			const line = doc.getLineHandle(index)

			const newvalue = (el.textContent === '[x]') ? '[ ]' : '[x]'

			// + 1 for some reason... not sure why
			doc.replaceRange(newvalue,
				{ line: index, ch: line.text.indexOf('[') },
				{ line: index, ch: line.text.indexOf(']') + 1 }
			)
		},

		onClickEditor(event) {
			const cm = event.target.closest('.CodeMirror')
			// if click is outside of editor, then jump to end position
			if (cm === null) {
				this.mde.codemirror.setCursor(this.mde.codemirror.lineCount(), 0)
				this.mde.codemirror.focus()
			}
		},

		async onClickSelect() {
			const apppath = '/' + store.state.app.settings.notesPath
			const categories = store.getters.getCategories()
			const currentNotePath = apppath + '/' + categories

			const doc = this.mde.codemirror.getDoc()
			const cursor = this.mde.codemirror.getCursor()
			OC.dialogs.filepicker(
				t('notes', 'Select an image'),
				(path) => {

					if (!path.startsWith(apppath)) {
						OC.dialogs.alert(
							t('notes', 'You cannot select images outside of your notes folder. Your notes folder is: {folder}', { folder: apppath }),
							t('notes', 'Wrong image'),
						)
						return
					}
					const noteLevel = ((currentNotePath + '/').split('/').length) - 1
					const imageLevel = (path.split('/').length - 1)
					const upwardsLevel = noteLevel - imageLevel
					for (let i = 0; i < upwardsLevel; i++) {
						path = '../' + path
					}
					path = path.replace(apppath + '/', '')
					doc.replaceRange('![' + path + '](' + path + ')', { line: cursor.line })
				},
				false,
				['image/jpeg', 'image/png'],
				true,
				OC.dialogs.FILEPICKER_TYPE_CHOOSE,
				currentNotePath
			)
		},

		async onClickUpload() {
			const doc = this.mde.codemirror.getDoc()
			const cursor = this.mde.codemirror.getCursor()
			const id = this.noteid

			const temporaryInput = document.createElement('input')
			temporaryInput.setAttribute('type', 'file')
			temporaryInput.onchange = async function() {
				const data = new FormData()
				data.append('file', temporaryInput.files[0])
				const response = await axios({
					method: 'POST',
					url: generateUrl('apps/notes') + '/notes/' + id + '/attachment',
					data,
				})
				console.log("ERROR: Response currently does not return filename")
				console.log(response)
				const position = {
					line: cursor.line,
				}
				doc.replaceRange('![' + name + '](' + name + ')', position)
			}
			temporaryInput.click()
		},

		insertText(content) {
			const doc = this.mde.codemirror.getDoc()
			const cursor = this.mde.codemirror.getCursor()
			const position = {
				line: cursor.line
			}
			doc.replaceRange(content, position)
		},

		surroundText(content) {
			const doc = this.mde.codemirror.getDoc()
			const cursorStart = this.mde.codemirror.getCursor('from')
			const cursorEnd = this.mde.codemirror.getCursor('to')
			const originalText = doc.getRange(cursorStart, cursorEnd)
			doc.replaceRange(content + originalText + content, cursorStart, cursorEnd)
		},

		insertLink() {
			this.insertText('[title](url)')
		},

		insertCheckbox() {
			this.insertText('- [ ] ')
		},

		makeBold() {
			this.surroundText('**')
		},

		makeItalic() {
			this.surroundText('_')
		},

		makeMonospace() {
			this.surroundText('`')
		},

		makeStrikethrough() {
			this.surroundText('~~')
		},

		undo() {
			this.mde.codemirror.undo()
		},

		redo() {
			this.mde.codemirror.redo()
		}
	},
}
</script>
<style>
@import '~easymde/dist/easymde.min.css';

.markdown-editor {
	min-height: 100%;
	padding-bottom: 30vh;
	padding-top: 1em;
}

.EasyMDEContainer .CodeMirror {
	min-height: 100%;
	line-height: 1.5em;
	border: none;
	color: inherit;
	background-color: inherit;
}

.CodeMirror-cursor {
	border-color: var(--color-main-text);
}

/* overwrite Nextcloud style for contenteditable */
.CodeMirror .CodeMirror-code {
	font-size: inherit;
	margin: 0;
	padding: 0;
}

/* text selection */
.CodeMirror .CodeMirror-selectedtext {
	background-color: var(--color-primary-element) !important;
	color: var(--color-primary-text) !important;
	opacity: 1 !important;
}

.CodeMirror .CodeMirror-selected {
	background: inherit !important;
}

/* fix for mobile */
.CodeMirror-code {
	width: 100% !important;
	border: none !important;
	background-color: inherit !important;
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

.CodeMirror .cm-link {
	color: var(--color-primary-element);
	text-decoration: none;
}

/* horizontal line */
.CodeMirror .cm-hr {
	display: inline-block;
	width: 100%;
	line-height: 0.25;
	background-color: var(--color-border-dark);
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
	width: 1.5em;
	color: var(--color-main-background);
}

.CodeMirror .cm-formatting-task.cm-meta::before,
.CodeMirror .cm-formatting-task.cm-property::before {
	content: '';
	width: 14px;
	height: 14px;
	position: absolute;
	background-color: var(--color-main-background);
	border: 1px solid #878787;
	border-radius: 1px;
	background-position: center;
	margin-top: 3px;
	margin-left: 2px;
}

.CodeMirror .cm-formatting-task.cm-property::before {
	background-image: var(--icon-checkmark-fff);
	background-color: var(--color-primary-element);
	border-color: var(--color-primary-element);
}

.CodeMirror .cm-formatting-task.cm-property + span {
	opacity: 0.5;
	text-decoration: line-through;
}

.toolbar {
	width: 100%;
	height: 44px;
	/*border-bottom: darkgray 1px solid;*/

	display:flex;
	justify-content:center;

}

.button {
	display: inline-block;
	height: 34px;
	padding: 4px 16px !important;
	border: none !important;
	background: none !important;
}

.button:hover{
	background: var(--color-background-hover) !important;
}

.toolbar_label {
	display: none;
}
</style>
