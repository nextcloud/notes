# Notes JavaScript development

You will need node.js version >= 0.8

## Build js
To build the js run:

    make

To build on changes run

	make watch
    
## Running unittests
Unittests are run with the testacular:

	make testacular

afterwards the watch command can be run in a new terminal:

	make watch

This will automatically execute unittests when a coffeescript file has been changed and saved.

### PHPUnit
To run phpunittests once a file changed, simply run

    make phpunit

## Clear build and node_modules folders
To clear the build/ folder run:

    make clean

## Run js unittests
To run js unittests with the ci server, use 

	make test