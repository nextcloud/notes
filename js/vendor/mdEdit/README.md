# mdEdit

Syntax-highlighted / semi-formatted markdown editor view with minimal dependencies.

As seen in my [online markdown editor](//jbt.github.io/markdown-editor) (the left-hand side is this editor, or more accurately it will be once I've merged [this pull request](https://github.com/jbt/markdown-editor/pull/25)).

Requires [Prism](//prismjs.com) - Prism core is required plus any languages you want to be syntax-highlighted inside fenced code blocks. The bundled `prism-all.js` includes _all_ available languages.

## Usage

 * Include `prism.css` and `mdedit.css`
 * Include `prism-all.js` and `mdedit.js`
 * Include a `<pre>` element where you want an editor
 * Then `var editor = mdEdit(thatPreElement, {options})`;

## API

 * The `options` parameter to the constructor may include the following configuration options:
   * `className` - any css classes to apply to the editor view
   * `change` - callback function that is called whenever the editor value changes (value is passed as an argument)

 * `editor.getValue()` - returns the current value of the editor view
 * `editor.setValue(val)` - sets the current value to `val` and updates the view
