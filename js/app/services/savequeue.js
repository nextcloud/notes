/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

app.factory('SaveQueue', ['$q', function($q) {
	var SaveQueue = function () {
		this.queue = {};
		this.flushLock = false;
	};

	SaveQueue.prototype = {
		add: function (note) {
			this.queue[note.id] = note;
			this.flush();
		},
		flush: function () {
			// if there are no changes dont execute the requests
			var keys = Object.keys(this.queue);
			if(keys.length === 0 || this.flushLock) {
				return;
			} else {
				this.flushLock = true;
			}

			var self = this;
			var requests = [];

			for(var i=0; i<keys.length; i++) {
				var note = this.queue[keys[i]];
				requests.push(note.put().then(this._noteUpdateRequest.bind(null, note)));
			}
			this.queue = {};

			$q.all(requests).then(function () {
				self.flushLock = false;
				self.flush();
			});
		},
		_noteUpdateRequest: function (note, response) {
			note.title = response.title;
			note.modified = response.modified;
		}
	};

	return new SaveQueue();
}]);