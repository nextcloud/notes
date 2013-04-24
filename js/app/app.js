/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */


// Create the main module and add OC (appframework js) to the container
// and register routes so the url is cool :)
var app = angular.module('Notes', ['OC']);

// This will be executed directly after angular has finished to initialize
app.run(['Storage', '$rootScope', function(Storage, $rootScope){

	// loads the notes from the server
	Storage.getAll(function() {
		$rootScope.$broadcast('notesLoaded');
	});

}]);