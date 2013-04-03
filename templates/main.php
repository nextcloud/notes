<?php 

\OCP\Util::addScript('appframework', 'vendor/angular/angular');
\OCP\Util::addScript('appframework', 'public/app');

\OCP\Util::addScript('notes', 'public/app');

\OCP\Util::addStyle('notes', 'notes');

?>

<div id="app" ng-app="Notes" ng-controller="NotesController">
	<div id="app-navigation">
		<ul>
			<li id="note-add" ng-click="createNew()"
				oc-click-focus="{selector: '#app-content textarea'}">
				<a href='#'>+ <span><?php p($l->t('New Note')); ?></span></a>
			</li>
			
			<li ng-repeat="note in notes|orderBy:'modified':'reverse'" 
				ng-show="note.content"
				ng-class="{active: note==activeNote}">
				<a href="#" ng-click="load(note)">{{ note.title }}</a>
			</li>
			
		</ul>
	</div>

	<div id="app-content"
		ng-class="{loading: loading.isLoading()}">
		<textarea ng-change="update(activeNote)" 
				  ng-model="activeNote.content"
				  ng-hide="loading.isLoading()"
				  autofocus></textarea>
	</div>
</div>
