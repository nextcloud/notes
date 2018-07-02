<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * Copyright (c) 2013, Jan-Christoph Borchardt http://jancborchardt.net
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


script('notes', [
    'vendor/angular/angular',
    'vendor/angular-route/angular-route',
    'vendor/restangular/dist/restangular',
    'vendor/simplemde/dist/simplemde.min',
    'public/app.min'
]);

style('notes', [
    '../js/vendor/simplemde/dist/simplemde.min',
    'notes'
]);

?>

<div id="app" ng-app="Notes" ng-controller="AppController"
    ng-init="init('<?= $_['lastViewedNote'] ?>','<?= $_['errorMessage'] ?>', <?= $_['useSearchAPI'] ?>)" ng-cloak>

    <script type="text/ng-template" id="note.html">
        <?php print_unescaped($this->inc('note')); ?>
    </script>

    <div id="app-navigation" ng-controller="NotesController" ng-class="{'loading': !notesLoaded}">
        <!-- new note button -->
        <div class="app-navigation-new">
            <button class="icon-add" id="new-note-button" type="button" ng-click="create()">
                <?php p($l->t('New note')); ?> 
            </button>
        </div>

        <ul>
<?php if(!$_['useSearchAPI']) { ?>
            <li class="note-search">
                <span class="nav-entry icon-search">
                    <input type="text" ng-model="search" />
                </span>
            </li>
<?php } ?>
            <!-- notes list -->
            <li ng-repeat="note in filteredNotes = (notes| and:search | orderBy:['-favorite','-modified'])"
                ng-class="{ active: note.id == route.noteId,'has-error': note.error }">
                <a href="#/notes/{{ note.id }}" title="{{ note.title }}">
                    {{ note.title }}
                    <span ng-if="note.unsaved">*</span>
                </a>
                <div class="app-navigation-entry-utils" ng-class="{'hidden': note.error }">
                    <ul>
                        <li class="app-navigation-entry-utils-menu-button button-delete">
                            <button class="svg action icon-delete"
                                title="<?php p($l->t('Delete note')); ?>"
                                notes-tooltip
                                data-placement="bottom"
                                ng-click="delete(note.id)"></button>
                        </li>
			<li class="app-navigation-entry-utils-menu-button button-star"
                            ng-class="{starred: note.favorite}"
                            >
                            <button class="svg action icon-star"
                                title="<?php p($l->t('Favorite')); ?>"
                                notes-tooltip
                                data-placement="bottom"
                                ng-click="toggleFavorite(note.id)"
                                ng-class="{'icon-starred': note.favorite}"></button>
                        </li>
                    </ul>
                </div>
            </li>
            <li ng-show="notesLoaded && !filteredNotes.length">
                <span class="nav-entry" ng-show="search">
                    <div id="emptycontent" class="emptycontent-search">
                        <div class="icon-search"></div>
                        <h2 class="ng-binding"><?php p($l->t('No search result for {{search}}')); ?></h2>
                    </div>
                </span>
                <span class="nav-entry" ng-show="!search"><?php p($l->t('No notes found')); ?></span>
            </li>

        </ul>

        <div id="app-settings" ng-controller="NotesSettingsController">
            <div id="app-settings-header">
                <button class="settings-button" data-apps-slide-toggle="#app-settings-content"><?php p($l->t('Settings'));?></button>
            </div>
            <div id="app-settings-content">
            <div class="settings-notesPath">
                <p class="settings-hint"><label for="notesPath"><?php p($l->t('Folder to store your notes')) ?></label></p>
                <input type="text" name="notesPath" ng-model="settings.notesPath" placeholder="<?php p($l->t('path to notes')); ?>" id="notesPath" style="width:80%"/><input type="submit" class="icon-confirm" value="">
            </div>
            <div class="settings-fileSuffix">
                <p class="settings-hint"><label for="fileSuffix"><?php p($l->t('File extension for new notes')) ?></label></p>
                <select id="fileSuffix" ng-model="settings.fileSuffix" ng-options="o as o for o in extensions"></select>
            </div>
            </div>
        </div>

    </div>

    <div id="app-content" ng-class="{loading: is.loading}">
        <div id="app-content-container" ng-view></div>
    </div>
</div>
