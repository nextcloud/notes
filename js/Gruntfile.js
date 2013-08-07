/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

module.exports = function(grunt) {

	// load needed modules
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-wrap');
	grunt.loadNpmTasks('grunt-phpunit');
	grunt.loadNpmTasks('gruntacular');


	grunt.initConfig({

		meta: {
			pkg: grunt.file.readJSON('package.json'),
			version: '<%= meta.pkg.version %>',
			production: 'public/'
		},

		concat: {
			options: {
				// remove license headers
				stripBanners: true,
				banner: '/**\n' +
				' * Copyright (c) 2013, Bernhard Posselt '+
				'<nukeawhale@gmail.com> \n' +
				' * This file is licensed under the Affero ' +
				'General Public License version 3 or later. \n' +
				' * See the COPYING file.\n */\n\n'
			},
			dist: {
				src: [
					'app/app.js',
					'app/directives/*.js',
					'app/controllers/*.js',
					'app/services/**/*.js'
				],
				dest: '<%= meta.production %>app.js'
			}
		},

		wrap: {
			app: {
				src: ['<%= meta.production %>app.js'],
				dest: '',
				wrapper: [
					'(function(angular, $, oc_requesttoken, undefined){\n\n\'use strict\';\n\n',
					'\n})(window.angular, jQuery, oc_requesttoken);'
				]
			}
		},

		jshint: {
			files: ['Gruntfile.js', 'app/**/*.js', 'tests/**/*.js'],
			options: {
				// options here to override JSHint defaults
				globals: {
					console: true
				}
			}
		},

		watch: {
			// this watches for changes in the app directory and runs the concat 
			// and wrap tasks if something changed
			concat: {
				files: [
					'app/**/*.js'
				],
				tasks: ['build']
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
	grunt.registerTask('build', ['jshint', 'concat', 'wrap']);
	grunt.registerTask('watchjs', ['watch:concat']);
	grunt.registerTask('ci', ['testacular:continuous']);
	grunt.registerTask('testphp', ['watch:phpunit']);
	grunt.registerTask('testjs', ['testacular:unit']);

};