
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
	--exclude=composer.json \
	--exclude=composer.lock \
	--exclude=composer.phar \
	--exclude=CONTRIBUTING.md \
	--exclude=.editorconfig \
	--exclude=.eslintrc.js \
	--exclude=.git \
	--exclude=.github \
	--exclude=.gitignore \
	--exclude=l10n/no-php \
	--exclude=Makefile \
	--exclude=package*.json \
	--exclude=phpcs.xml \
	--exclude=phpunit*xml \
	--exclude=.scrutinizer.yml \
	--exclude=src \
	--exclude=.stylelintrc.js \
	--exclude=tests \
	--exclude=.travis.yml \
	--exclude=.tx \
	--exclude=vendor \
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
lint: lint-php lint-js lint-css

lint-php:
	vendor/bin/phpcs --standard=phpcs.xml --runtime-set ignore_warnings_on_exit 1 appinfo/ lib/

lint-js:
	npm run lint

lint-css:
	npm run stylelint

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
	rm -f js/notes.js
	rm -f js/notes.js.map

clean-dev:
	rm -rf node_modules
	rm -rf vendor

