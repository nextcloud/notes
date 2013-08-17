/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file. 
 */

// Testacular configuration
// Generated on Tue Feb 12 2013 19:27:01 GMT+0100 (CET)


// base path, that will be used to resolve files and exclude
basePath = '../';


// list of files / patterns to load in the browser
files = [
	JASMINE,
	JASMINE_ADAPTER,
	'vendor/jquery/jquery.js',
	'vendor/angular/angular.js',
	'vendor/angular/angular-mocks.js',
	'../../appframework/js/tests/stubs/owncloud.js',
	'../../appframework/js/public/app.js',
	'tests/stubs/modules.js',
	'app/directives/*.js',
	'app/filters/*.js',
	'app/services/**/*.js',
	'app/controllers/**/*.js',
	'tests/**/*Spec.js'
];


// list of files to exclude
exclude = [
	'app/app.js'
];


// test results reporter to use
// possible values: 'dots', 'progress', 'junit'
reporters = ['progress'];


// web server port
port = 8080;


// cli runner port
runnerPort = 9100;


// enable / disable colors in the output (reporters and logs)
colors = true;


// level of logging
// possible values: LOG_DISABLE || LOG_ERROR || LOG_WARN || LOG_INFO || LOG_DEBUG
logLevel = LOG_INFO;


// enable / disable watching file and executing tests whenever any file changes
autoWatch = true;


// Start these browsers, currently available:
// - Chrome
// - ChromeCanary
// - Firefox
// - Opera
// - Safari (only Mac)
// - PhantomJS
// - IE (only Windows)
browsers = ['Chrome'];


// If browser does not capture in given timeout [ms], kill it
captureTimeout = 5000;


// Continuous Integration mode
// if true, it capture browsers, run tests and exit
singleRun = false;
