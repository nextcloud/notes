/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file. 
 */

module.exports = function(grunt) {

	// load needed modules
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-wrap');
	grunt.loadNpmTasks('grunt-phpunit');
	grunt.loadNpmTasks('gruntacular');

	grunt.initConfig({

		meta: {
			pkg: grunt.file.readJSON('package.json'),
			version: '<%= meta.pkg.version %>',
			production: 'public/',
			build: 'build/'
		},

		concat: {
			src: [
				'app/app.js',
				'app/directives/*.js',
				'app/controllers/*.js',
				'app/services/**/*.js'
			],
			dest: '<%= meta.build %>app.js'
		},

		wrap: {
			src: '<%= meta.build %>app.js',
			dest: '<%= meta.production %>app.js',
			wrapper: [
				'(function(angular, $, undefined){\n\n\t\'use strict\';\n',
				'\n})(window.angular, jQuery);'
			]
		},

		watch: {
			// this watches for changes in the app directory and runs the concat 
			// and wrap tasks if something changed
			concat: {
				files: [
					'app/**/*.js'
				],
				tasks: ['concat', 'wrap']
			},
			phpunit: {
				files: '../**/*.php',
				tasks: 'phpunit'
			}
		},

		phpunit: {
			classes: {
				dir: '../tests'
			},
			options: {
				colors: true
			}
		},

		testacular: {
			unit: {
				configFile: 'config/testacular_conf.js'
			},
			continuous: {
				configFile: 'config/testacular_conf.js',
				singleRun: true,
				browsers: ['PhantomJS'],
				reporters: ['progress', 'junit'],
				junitReporter: {
					outputFile: 'test-results.xml'
				}
			}
		}

	});

	// make tasks available under simpler commands
	grunt.registerTask('watchjs', ['watch:concat']);
	grunt.registerTask('ci', ['testacular:continuous']);
	grunt.registerTask('testphp', ['watch:phpunit']);
	grunt.registerTask('testjs', ['testacular:unit']);

};