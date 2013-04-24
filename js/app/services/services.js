/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

// Use this to instantiate and build the objects from the appframework


// dependency of the request object
app.factory('Publisher',

	['_Publisher', 'NotesModel',
	function(_Publisher, NotesModel) {


	var publisher = new _Publisher();

	// distribute all content that is being returned in the data.notes json array
	// to the model. This adds new notes and updates existing ones
	publisher.subscribeObjectTo(NotesModel, 'notes');

	return publisher;

}]);


// this allows you to make ajax requests 
app.factory('Request',

	['_Request', '$http', 'Publisher', 'Router',
	function(_Request, $http, Publisher, Router) {

	return new _Request($http, Publisher, Router);

}]);


// loading spinner
app.factory('Loading', ['_Loading', function(_Loading){
	return new _Loading();
}]);
