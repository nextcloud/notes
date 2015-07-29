# Building JavaScript and CSS

To build JavaScript and CSS first install Gulp and install the required modules via npm:

    sudo npm install -g gulp
    npm install

To simply build everything run:

    gulp

You can also run it in watch mode to rebuild when files change:

    gulp watch

# Tests

Run all tests:

    gulp test-all

Run JavaScript tests:

    gulp test

in watch mode:

    gulp watch-test

Run PHP unit tests:

    gulp test-php

in watch mode:

    gulp watch-test-php


Run PHP integration tests:

    gulp test-php-integration

