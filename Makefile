#
# ownCloud scaffolder tool
#
# Copyright (C) 2013 Bernhard Posselt, <nukewhale@gmail.com>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


# Makefile for building the project
app_name=notes
project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
sign_dir=$(build_dir)/sign
appstore_dir=$(build_dir)/appstore
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates

# binary directories for running the CI tests
firefox_bin=/usr/bin/firefox
chrome_bin=/usr/bin/chromium
phantomjs_bin=/usr/bin/phantomjs

js_dir=$(CURDIR)/js
js_public_dir=$(js_dir)/public

# common directories
grunt_dir=$(js_dir)/node_modules/grunt-cli/bin/grunt
bower_dir=$(js_dir)/node_modules/bower/bin/bower
gruntfile_dir=$(js_dir)/Gruntfile.js

php_unit_tests_dir=$(CURDIR)/tests/unit
php_integration_tests_dir=$(CURDIR)/tests/integration
php_acceptance_tests_dir=$(CURDIR)/tests/acceptance



# building the javascript
all: build

build: deps
	mkdir -p $(js_public_dir)
	$(grunt_dir) --config $(gruntfile_dir) build

watch: build
	$(grunt_dir) --config $(gruntfile_dir) watch:concat

update: deps
	$(bower_dir) update

# testing
tests: js-unit-tests php-unit-tests php-integration-tests php-acceptance-tests

unit-tests: js-unit-tests php-unit-tests


# testing js
js-unit-tests: deps
	export PHANTOMJS_BIN=$(phantomjs_bin) && \
	$(grunt_dir) --config $(gruntfile_dir) karma:continuous

watch-js-unit-tests: deps
	export CHROME_BIN=$(chrome_bin) && export FIREFOX_BIN=$(firefox_bin) && \
	$(grunt_dir) --config $(gruntfile_dir) karma:unit


# testing php
php-unit-tests: deps
	phpunit $(php_unit_tests_dir)

watch-php-unit-tests: deps
	$(grunt_dir) --config $(gruntfile_dir) watch:phpunit

php-integration-tests: deps
	phpunit $(php_integration_tests_dir)

php-acceptance-tests: deps
	cd $(php_acceptance_tests_dir); make headless


# general
deps:
	cd js
	npm install --deps
	cd ..

clean:
	rm -rf $(CURDIR)/node_modules
	rm -rf $(build_dir)

dist: appstore

appstore: clean
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=.git \
	--exclude=build \
	--exclude=.gitignore \
	--exclude=.travis.yml \
	--exclude=.scrutinizer.yml \
	--exclude=CONTRIBUTING.md \
	--exclude=composer.json \
	--exclude=composer.lock \
	--exclude=composer.phar \
	--exclude=l10n/.tx \
	--exclude=l10n/no-php \
	--exclude=Makefile \
	--exclude=nbproject \
	--exclude=screenshots \
	--exclude=phpunit*xml \
	--exclude=tests \
	--exclude=vendor/bin \
	--exclude=js/node_modules \
	--exclude=js/tests \
	--exclude=js/karma.conf.js \
	--exclude=js/gulpfile.js \
	--exclude=js/bower.json \
	--exclude=js/package.json \
	$(project_dir) $(sign_dir)
	@echo "Signing…"
	php ../../occ integrity:sign-app \
		--privateKey=$(cert_dir)/$(app_name).key\
		--certificate=$(cert_dir)/$(app_name).crt\
		--path=$(sign_dir)/$(app_name)
	tar -czf $(build_dir)/$(app_name).tar.gz \
		-C $(sign_dir) $(app_name)
	openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(build_dir)/$(app_name).tar.gz | openssl base64
