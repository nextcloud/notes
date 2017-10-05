<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * Copyright (c) 2013, Jan-Christoph Borchardt http://jancborchardt.net
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


script('notes', [
    'vendor/bootstrap/tooltip',
    'vendor/angular/angular',
    'vendor/angular-route/angular-route',
    'vendor/restangular/dist/restangular',
    'vendor/underscore/underscore',
    'vendor/simplemde/dist/simplemde.min',
    'public/app.min'
]);

style('notes', [
    '../js/vendor/simplemde/dist/simplemde.min',
    'vendor/bootstrap/tooltip',
    'notes'
]);

?>

<div id="app" ng-app="Notes" ng-controller="AppController"
    ng-init="init(<?php p($_['lastViewedNote']); ?>)" ng-cloak>

    <script type="text/ng-template" id="note.html">
        <?php print_unescaped($this->inc('note')); ?>
    </script>

    <div id="app-navigation" ng-controller="NotesController">
        <ul>
            <li class="note-search">
                <span class="nav-entry icon-search">
                    <input type="text" ng-model="search" />
                </span>
            </li>
            <!-- new note button -->
            <div id="note-add">            
                <button class="icon-add app-content-list-button ng-binding" id="new-note-button" type="button" name="button" ng-click="create()"
                oc-click-focus="{ selector: '#app-content textarea' }">
                    <?php p($l->t('New note')); ?> 
                </button>
            </div>
            <!-- notes list -->
            <li ng-repeat="note in filteredNotes = (notes| and:search | orderBy:['-favorite','-modified'])"
                ng-class="{ active: note.id == route.noteId }">
                <a href="#/notes/{{ note.id }}">
                    {{ note.title | noteTitle }}
                    <span ng-if="note.unsaved">*</span>
                </a>
                <span class="utils">
                    <button class="svg action icon-delete"
                        title="<?php p($l->t('Delete note')); ?>"
                        notes-tooltip
                        data-placement="bottom"
                        ng-click="delete(note.id)"></button>
                    <button class="svg action icon-star"
                        title="<?php p($l->t('Favorite')); ?>"
                        notes-tooltip
                        data-placement="bottom"
                        ng-click="toggleFavorite(note.id)"
                        ng-class="{'icon-starred': note.favorite}"></button>
                </span>
            </li>
            <li ng-hide="filteredNotes.length">
                <span class="nav-entry">
                    <?php p($l->t('No notes found')); ?>
                </span>
            </li>

        </ul>
    </div>

    <div id="app-content" ng-class="{loading: is.loading}">
        <div id="app-content-container" ng-view></div>
    </div>
</div>
