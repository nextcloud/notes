<?php 
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * Copyright (c) 2013, Jan-Christoph Borchardt http://jancborchardt.net
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

\OCP\Util::addScript('appframework', 'vendor/angular/angular');
\OCP\Util::addScript('appframework', 'public/app');

\OCP\Util::addScript('notes', 'public/app');

\OCP\Util::addStyle('notes', 'notes');

?>

<div id="app" ng-app="Notes" ng-controller="NotesController">
	<div id="app-navigation">
		<ul>
			<!-- new note button -->
			<li id="note-add" ng-click="createNew()"
				oc-click-focus="{ selector: '#app-content textarea' }">
				<a href='#'>+ <span><?php p($l->t('New Note')); ?></span></a>
			</li>
			<!-- notes list -->
			<li ng-repeat="note in notes|orderBy:'modified':'reverse'" 
				ng-class="{ active: note == activeNote }">
				<a href="#{{ note.id }}"
					oc-click-focus="{ selector: '#app-content textarea' }">
					{{ note.title }}
				</a>
			</li>
			
		</ul>
	</div>

	<div id="app-content" ng-class="{ loading: loading.isLoading() }">
		<!-- actual note -->
		<textarea
			  ng-model="activeNote.content"
			  ng-change="activeNote.dirty=true"
			  ng-hide="loading.isLoading()"
			  autofocus
			  tabindex="-1">
		</textarea>
	</div>
</div>
