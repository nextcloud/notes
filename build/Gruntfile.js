
/**
 * ownCloud - Notes app
 *
 * @author Bernhard Posselt
 *
 * @copyright 2013 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


module.exports = function(grunt) {

	// load needed modules
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-wrap');
	grunt.loadNpmTasks('grunt-karma');
	grunt.loadNpmTasks('grunt-phpunit');


	grunt.initConfig({

		meta: {
			pkg: grunt.file.readJSON('package.json'),
			version: '<%= meta.pkg.version %>',
			production: '../js/public/'
		},

		concat: {
			options: {
				// remove license headers
				stripBanners: true
			},
			dist: {
				src: [
					'../js/polyfills/**/*.js',
					'../js/config/app.js',
					'../js/app/**/*.js'
				],
				dest: '<%= meta.production %>app.js'
			}
		},

		wrap: {
			app: {
				src: ['<%= meta.production %>app.js'],
				dest: '<%= meta.production %>',
				wrapper: [
					'(function(angular, $, oc_requesttoken, undefined){\n\n\'use strict\';\n\n',
					'\n})(angular, jQuery, oc_requesttoken);'
				]
			}
		},

		jshint: {
			files: [
				'Gruntfile.js',
				'../js/app/**/*.js',
				'../js/config/*.js',
				'../tests/js/unit/**/*.js'
			],
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
					'../js/polyfills/**/*.js',
					'../js/app/**/*.js',
					'../js/config/*.js',
					'../css/**/*.css',
					'../templates/**/*.php'
				],
				options: {
					livereload: true
				},
				tasks: ['build']
			},
			phpunit: {
				files: '../**/*.php',
				tasks: ['phpunit']
			}
		},

		phpunit: {
			classes: {
				dir: '../tests/php/unit'
			},
			options: {
				colors: true
			}
		},

		karma: {
			unit: {
				configFile: '../tests/js/config/karma.js'
			},
			continuous: {
				configFile: '../tests/js/config/karma.js',
				singleRun: true,
				browsers: ['PhantomJS'],
				reporters: ['progress']
			}
		}

	});

	// make tasks available under simpler commands
	grunt.registerTask('build', ['jshint', 'concat', 'wrap:app']);

};