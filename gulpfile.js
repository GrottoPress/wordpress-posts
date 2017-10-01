/**
 * Gulpfile
 *
 * @see https://travismaynard.com/writing/getting-started-with-gulp
 *
 * @since 0.1.0
 */

'use strict';

// Include gulp
const gulp = require('gulp');

// Include Our Plugins
const rename = require('gulp-rename');
const rtlcss = require('gulp-rtlcss');
const cleanCSS = require('gulp-clean-css');
const sass = require('gulp-sass');

// Files/Paths
const sass_src = './src/assets/styles';
const sass_file = [sass_src+'/posts.scss'];
const sass_files = [sass_src+'/**/*.scss'];
const sass_dest = './dist/assets/styles';

// Compile scss, rtl and minify scss
gulp.task('compile_sass', function () {
    return gulp.src(sass_file)
        .pipe(sass().on('error', sass.logError))
        // .pipe(cleanCSS({format: 'beautify'}))
        .pipe(gulp.dest(sass_dest))
        .pipe(cleanCSS())
        .pipe(rename({'suffix' : '.min'}))
        .pipe(gulp.dest(sass_dest))
        .pipe(rtlcss())
        .pipe(rename(function (path) {
            path.basename = path.basename.replace('.min', '-rtl.min');
        }))
        .pipe(gulp.dest(sass_dest));
});

// Watch Files For Changes
gulp.task('watch', function () {
    gulp.watch(sass_files, ['compile_sass']);
});

// Default Task
gulp.task('default', [
    'compile_sass',
    'watch'
]);
