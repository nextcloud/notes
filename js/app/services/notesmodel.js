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

		// in case there is no id, get the highest id
		var query = new _MaximumQuery('id');
		var result = this.get(query);

		var id = 1;
		// if there is no id (no notes), start with 1
		if(angular.isDefined(result)){
			id = result.id + 1;
		}

		data.id = id;

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
