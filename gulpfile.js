/**
 * Gulpfile
 *
 * @since 0.1.0
 */

'use strict'

/**
 * Import gulp, plugins
 */
const gulp = require('gulp')
const rename = require('gulp-rename')
const rtlcss = require('gulp-rtlcss')
const cleanCSS = require('gulp-clean-css')
const sass = require('gulp-sass')
const sourcemaps = require('gulp-sourcemaps')

/**
 * File paths
 */
const sass_files = ['./assets/styles/**/*.scss']
const sass_dest = './dist/styles'

/**
 * Compile scss, rtl and minify css
 */
gulp.task('compile-sass', () =>
    gulp.src(sass_files)
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    // .pipe(cleanCSS({format: 'beautify'}))
    // .pipe(gulp.dest(sass_dest))
    .pipe(cleanCSS())
    .pipe(rename({'suffix' : '.min'}))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(sass_dest))
    .pipe(rtlcss())
    .pipe(rename((path) =>
        path.basename = path.basename.replace('.min', '-rtl.min')
    ))
    .pipe(gulp.dest(sass_dest))
)

/**
 * Watch files for changes
 */
gulp.task('watch', () =>
    gulp.watch(sass_files, ['compile-sass'])
)

/**
 * Default task
 */
gulp.task('default', [
    'compile-sass',
    'watch'
])
