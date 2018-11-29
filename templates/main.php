<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * Copyright (c) 2013, Jan-Christoph Borchardt http://jancborchardt.net
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


script('notes', [
    'vendor/angular/angular.min',
    'vendor/angular-route/angular-route.min',
    'vendor/restangular/dist/restangular.min',
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

{{ SelectedCategory = (filterCategory==null ? null : filterCategory || '<?php p($l->t('Uncategorized')); ?>'); ''}}

        <!-- new note button -->
        <div class="app-navigation-new">
            <button class="icon-add" id="new-note-button" type="button" ng-click="create()">
                <?php p($l->t('New note')); ?> 
            </button>
        </div>

        <ul class="with-icon">

	    <!-- old style search (before NC 14) -->
<?php if(!$_['useSearchAPI']) { ?>
            <li class="note-search">
                <span class="nav-entry icon-search">
                    <input type="text" ng-model="search" />
                </span>
            </li>
<?php } ?>

            <!-- category selector -->
            <li class="collapsible app-navigation-noclose separator-below" ng-class="{ open: folderSelectorOpen, 'current-category-item': !folderSelectorOpen && filterCategory!=null }" ng-show="notes.length>1">
                <a class="nav-icon-files svg" ng-click="toggleFolderSelector()">{{!folderSelectorOpen && SelectedCategory ? SelectedCategory : '<?php p($l->t('Categories')); ?>' | categoryTitle}}</a>

                <ul>
                <li data-id="recent" class="nav-recent" ng-class="{ active: filterCategory==null && filterFavorite==false }" ng-show="notes.length>1">
                        <a
                            ng-click="setFilter(null)"
                            class="nav-icon-recent svg"
                        ><?php p($l->t('All notes')); ?></a>
                       <div class="app-navigation-entry-utils">
                           <ul>
                               <li class="app-navigation-entry-utils-counter">{{notes.length}}</li>
                           </ul>
                      </div>
                </li>

                <!-- category list -->
                <li
                  ng-repeat="category in (categories | orderBy:['name'])"
                  class="nav-files"
                  ng-class="{ active: filterCategory==category.name && filterFavorite==false }"
                  title="{{ category.name || '<?php p($l->t('Uncategorized')); ?>' }}"
                  >
                       <a
                           ng-click="setFilter(category.name)"
                           class="svg"
                           ng-class="{ 'nav-icon-emptyfolder': !category.name, 'nav-icon-files': category.name }"
                       >{{ category.name || '<?php p($l->t('Uncategorized')); ?>' }}</a>
                       <div class="app-navigation-entry-utils">
                           <ul>
                               <li class="app-navigation-entry-utils-counter">{{category.count}}</li>
                           </ul>
                      </div>
                </li>
                </ul>
            </li>

            <!-- search result header -->
            <li ng-show="search && filteredNotes.length" class="search-result-header">
		<a class="nav-icon-search active">
                    <span ng-show="SelectedCategory"><?php p($l->t('Search result for "{{search}}" in {{SelectedCategory}}')); ?></span>
                    <span ng-show="!SelectedCategory"><?php p($l->t('Search result for "{{search}}"')); ?></span>
                </a>
	    </li>

	    <!-- nothing found -->
            <li ng-show="notesLoaded && !filteredNotes.length">
                <span class="nav-entry" ng-show="search">
                    <div id="emptycontent" class="emptycontent-search">
                        <div class="icon-search"></div>
                        <h2 ng-show="SelectedCategory"><?php p($l->t('No search result for {{search}} in {{SelectedCategory}}')); ?></h2>
                        <h2 ng-show="!SelectedCategory"><?php p($l->t('No search result for {{search}}')); ?></h2>
                    </div>
                </span>
                <span class="nav-entry" ng-show="!search"><?php p($l->t('No notes found')); ?></span>
            </li>

            <!-- notes list -->
            <li ng-repeat="note in filteredNotes = (notes | filter:categoryFilter | and:search | orderBy:filterOrder | groupNotes:filterCategory)"
                ng-class="{ active: note.id == route.noteId, 'has-error': note.error, 'app-navigation-noclose': isCategory(note) }"
                class="note-item">

		<a class="nav-icon-files svg separator-above"
		   ng-if="isCategory(note)"
                   ng-click="setFilter(filterCategory + '/' + note)"
                   >&hellip; / {{ note | categoryTitle }}</a>

                <a href="#/notes/{{ note.id }}"
		   title="{{ note.title }}"
                   ng-if="!isCategory(note)"
                   >
                    {{ note.title }}
                    <span ng-if="note.unsaved">*</span>
                </a>
                <div class="app-navigation-entry-utils" ng-class="{'hidden': note.error }" ng-if="!isCategory(note)">
                    <ul>
                        <li class="app-navigation-entry-utils-menu-button button-delete">
                            <button class="svg action icon-delete"
                                title="<?php p($l->t('Delete note')); ?>"
                                notes-tooltip
                                data-placement="bottom"
                                data-trigger="hover"
                                ng-click="delete(note.id)"></button>
                        </li>
			<li class="app-navigation-entry-utils-menu-button button-star"
                            ng-class="{starred: note.favorite}"
                            >
                            <button class="svg action icon-star"
                                title="<?php p($l->t('Favorite')); ?>"
                                notes-tooltip
                                data-placement="bottom"
                                data-trigger="hover"
                                ng-click="toggleFavorite(note.id, $event)"
                                ng-class="{'icon-starred': note.favorite}"></button>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

        <div id="app-settings" ng-controller="NotesSettingsController">
            <div id="app-settings-header">
                <button class="settings-button" data-apps-slide-toggle="#app-settings-content"><?php p($l->t('Settings'));?></button>
            </div>
            <div id="app-settings-content">
            <div class="settings-notesPath">
                <p class="settings-hint"><label for="notesPath"><?php p($l->t('Folder to store your notes')) ?></label></p>
                <form><input type="text" name="notesPath" ng-model="settings.notesPath" placeholder="<?php p($l->t('path to notes')); ?>" id="notesPath"/><input type="submit" class="icon-confirm" value=""></form>
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
