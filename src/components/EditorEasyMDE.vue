<template>
	<div>
		<div class="upload-button">
			<NcActions
				container=".upload-button"
				default-icon="icon-picture"
				menu-align="right"
			>
				<NcActionButton
					icon="icon-upload"
					:close-after-click="true"
					@click="onClickUploadImage"
				>
					{{ t('notes', 'Upload image') }}
				</NcActionButton>
				<NcActionButton
					icon="icon-picture"
					:close-after-click="true"
					@click="onClickInsertImage"
				>
					{{ t('notes', 'Insert image') }}
				</NcActionButton>
			</NcActions>
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
import { showError } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/dist/index.css'
import {
	NcActions,
	NcActionButton,
} from '@nextcloud/vue'
import { basename, relative } from 'path'

import store from '../store.js'

export default {
	name: 'EditorEasyMDE',

	components: {
		NcActions,
		NcActionButton,
	},

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
		notecategory: {
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
				shortcuts: {
					toggleSideBySide: null,
					togglePreview: null,
				},
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

			// clear initial empty state in history
			this.mde.codemirror.clearHistory()
		},

		onClickCodeElement(event) {
			const element = event.target.closest('.cm-formatting-task')
			if (element !== null && !this.readonly) {
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

		async onClickInsertImage() {
			const apppath = '/' + store.state.app.settings.notesPath + '/'
			const currentNotePath = apppath + this.notecategory

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
					const label = basename(path)
					const relativePath = relative(currentNotePath, path)
					const encodedPath = relativePath.split('/').map(encodeURIComponent).join('/')
					doc.replaceRange('![' + label + '](' + encodedPath + ')\n', { line: cursor.line })
					this.mde.codemirror.focus()
				},
				false,
				['image/jpeg', 'image/png'],
				true,
				OC.dialogs.FILEPICKER_TYPE_CHOOSE,
				currentNotePath
			)
		},

		async onClickUploadImage() {
			const cm = this.mde.codemirror
			const doc = this.mde.codemirror.getDoc()
			const cursor = this.mde.codemirror.getCursor()
			const id = this.noteid

			const temporaryInput = document.createElement('input')
			temporaryInput.setAttribute('type', 'file')
			temporaryInput.onchange = async function() {
				const data = new FormData()
				data.append('file', temporaryInput.files[0])
				const originalFilename = temporaryInput.files[0].name

				axios.post(generateUrl('apps/notes') + '/notes/' + id + '/attachment', data)
					.then((response) => {
						const name = response.data.filename
						const position = {
							line: cursor.line,
						}
						doc.replaceRange('![' + originalFilename + '](' + name + ')\n', position)
						cm.focus()
					})
					.catch((error) => {
						console.error(error)
						showError(t('notes', 'The file was not uploaded. Check your server logs.'),)
					})
			}
			temporaryInput.click()
		},
	},
}
</script>
<style>
@import '~easymde/dist/easymde.min.css';

.markdown-editor {
	min-height: 100%;
	padding-bottom: 30vh;
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
	color: var(--color-primary-element-text) !important;
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
	border-radius: var(--border-radius);
	background-position: center;
	margin-top: 5px;
	margin-left: 2px;
}

.CodeMirror .cm-formatting-task.cm-property::before {
	background-image: var(--icon-checkmark-white);
	background-color: var(--color-primary-element);
	border-color: var(--color-primary-element);
}

.CodeMirror .cm-formatting-task.cm-property ~ span {
	opacity: 0.5;
	text-decoration: line-through;
}

.upload-button {
	position: fixed;
	right: 64px;
	z-index: 10;
	height: 40px;
	margin-right: 5px;
	top: 65px;
}
</style>
