/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file. 
 */

// loading spinner
app.factory('Loading', ['_Loading', function(_Loading){
	return new _Loading();
}]);
