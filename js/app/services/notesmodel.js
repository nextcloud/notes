/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

// used to store the notes data and create hashes and caches for quick access
app.factory('NotesModel',

	['_Model', '_EqualQuery', '_MaximumQuery',
	function(_Model, _EqualQuery, _MaximumQuery){

	var NotesModel = function(){};
	NotesModel.prototype = new _Model();

	// overwrite to set an id
	NotesModel.prototype.add = function(data) {
		_Model.prototype.add.call(this, data);
	};


	NotesModel.prototype.getNewest = function() {
		var query = new _MaximumQuery('modified');
		return this.get(query);
	};


	NotesModel.prototype.getByTitle = function(title){
		var query = new _EqualQuery('title', title);
		return this.get(query);
	};


	return new NotesModel();

}]);
