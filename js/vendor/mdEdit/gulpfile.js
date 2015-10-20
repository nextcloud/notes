var gulp = require('gulp');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var uglify = require('gulp-uglify');
var wrap = require('gulp-wrap-umd');
var del = require('del');

gulp.task('build', function(){
  return gulp.src([
      'src/yaml.js',
      'src/md.js',
      'src/util.js',
      'src/actions.js',
      'src/SelectionManager.js',
      'src/UndoManager.js',
      'src/Editor.js'
    ])
    .pipe(concat('mdedit.js'))
    .pipe(wrap({
      namespace: 'mdEdit',
      deps: [
        { name: 'prismjs', paramName: 'Prism', globalName: 'Prism', amdName: 'prismjs' }
      ],
      exports: 'Editor'
    }))
    .pipe(gulp.dest('./'))
    .pipe(rename({ suffix: '.min' }))
    .pipe(uglify())
    .pipe(gulp.dest('./'));
});

gulp.task('clean', function(cb) {
  del(['mdedit.js', 'mdedit.min.js'], cb);
});
