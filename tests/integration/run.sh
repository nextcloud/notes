#!/usr/bin/env bash

OC_PATH=../../../../

composer install
composer dump-autoload

php -S localhost:8080 -t $OC_PATH &

vendor/bin/behat
