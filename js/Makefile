# Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
#  This file is licensed under the Affero General Public License version 3 or later.
#  See the COPYING file. 

firefox_bin=/usr/bin/firefox
chrome_bin=/usr/bin/chromium
grunt=$(CURDIR)/node_modules/grunt-cli/bin/grunt
phantomjs=$(CURDIR)/node_modules/phantomjs/bin/phantomjs

all: build

build: deps
	mkdir -p $(CURDIR)/public
	$(grunt) --config $(CURDIR)/Gruntfile.js build

deps:
	cd $(CURDIR)/
	npm install --deps

watch: build
	$(grunt) --config $(CURDIR)/Gruntfile.js watchjs

testacular: deps
	export CHROME_BIN=$(chrome_bin) && export FIREFOX_BIN=$(firefox_bin) && \
	$(grunt) --config $(CURDIR)/Gruntfile.js testjs
	
phpunit: deps
	$(grunt) --config $(CURDIR)/Gruntfile.js testphp

test: deps
	export PHANTOMJS_BIN=$(phantomjs) && \
	$(grunt) --config $(CURDIR)/Gruntfile.js ci

clean:
	rm -rf $(CURDIR)/node_modules/
	rm -rf $(CURDIR)/test-results.xml

phpcoverage:
	cd $(CURDIR)/../
	phpunit --coverage-html ./report tests/
