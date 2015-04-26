<div class="utils">
    <label>
        <input type="checkbox"
            ng-model="markdown"
            name="markdown"
            ng-change="sync(markdown)"> Markdown
    </label>
</div>

<div id="app-navigation-toggle" class="icon-menu" style="display:none;"></div>

<textarea
	ng-model="note.content"
    ng-class="{markdown: config.isMarkdown(), saving: isSaving()}"
    ng-change="updateTitle()"
    notes-is-saving="isSaving()"
	notes-timeout-change="save()"
    notes-autofocus
	tabindex="-1"></textarea>
<div markdown="note.content" class="markdown" ng-if="config.isMarkdown()">
</div>