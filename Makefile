
### build and sign release

app_name=notes
project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
sign_dir=$(build_dir)/sign
appstore_dir=$(build_dir)/appstore
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates

appstore: clean lint build-js-production
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=.babelrc.js \
	--exclude=build \
	--exclude=composer.* \
	--exclude=CONTRIBUTING.md \
	--exclude=.editorconfig \
	--exclude=.eslintrc.js \
	--exclude=.git \
	--exclude=.github \
	--exclude=.gitignore \
	--exclude=l10n/no-php \
	--exclude=Makefile \
	--exclude=node_modules \
	--exclude=package*.json \
	--exclude=phpunit*xml \
	--exclude=.scrutinizer.yml \
	--exclude=src \
	--exclude=.stylelintrc.js \
	--exclude=tests \
	--exclude=.travis.yml \
	--exclude=.tx \
	--exclude=vendor \
	--exclude=webpack.*.js \
	$(project_dir) $(sign_dir)
	@echo "Signing…"
	php ../server/occ integrity:sign-app \
		--privateKey=$(cert_dir)/$(app_name).key\
		--certificate=$(cert_dir)/$(app_name).crt\
		--path=$(sign_dir)/$(app_name)
	tar -czf $(build_dir)/$(app_name).tar.gz \
		-C $(sign_dir) $(app_name)
	openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(build_dir)/$(app_name).tar.gz | openssl base64


### from vueexample

all: dev-setup lint build-js-production test

# Dev env management
dev-setup: clean clean-dev init

init: composer-init npm-init

composer-init:
	composer install

npm-init:
	npm install

npm-upgrade:
	npm-upgrade
	npm install

npm-update:
	npm update

# Building
build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

# Testing
test:
	npm run test

test-watch:
	npm run test:watch

test-coverage:
	npm run test:coverage


# Linting
lint: lint-php lint-js lint-css lint-xml


lint-php: lint-php-lint lint-php-ncversion lint-php-phan lint-php-phpcs
lint-phpfast: lint-php-lint lint-php-ncversion lint-php-phpcs

lint-php-lint:
	# Check PHP syntax errors
	@! find lib/ -name "*.php" | xargs -I{} php -l '{}' | grep -v "No syntax errors detected"

lint-php-ncversion:
	# Check min-version consistency
	php tests/nextcloud-version.php

lint-php-phan:
	# PHAN
	vendor/bin/phan --allow-polyfill-parser -k tests/phan-config.php --no-progress-bar -m checkstyle | vendor/bin/cs2pr --colorize

lint-php-phpcs:
	# PHP CodeSniffer
	vendor/bin/phpcs --standard=tests/phpcs.xml appinfo/ lib/ --report=checkstyle | vendor/bin/cs2pr --graceful-warnings --colorize


lint-js:
	npm run lint

lint-css:
	npm run stylelint

lint-xml:
	# Check info.xml schema validity
	wget https://apps.nextcloud.com/schema/apps/info.xsd -P appinfo/ -N --no-verbose || [ -f appinfo/info.xsd ]
	xmllint appinfo/info.xml --schema appinfo/info.xsd --noout


# Fix lint
lint-fix: lint-php-fix lint-js-fix lint-css-fix

lint-php-fix:
	vendor/bin/phpcbf --standard=phpcs.xml appinfo/ lib/

lint-js-fix:
	npm run lint:fix

lint-css-fix:
	npm run stylelint:fix

# Cleaning
clean:
	rm -rf js/
	rm -rf $(build_dir)

clean-dev:
	rm -rf node_modules
	rm -rf vendor

