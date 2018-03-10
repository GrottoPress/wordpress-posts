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
 * Define paths
 */
const styles_src = ['./assets/styles/**/*.scss']
const styles_dest = './dist/styles'
const vendor_assets = './assets/vendor'

/**
 * Build styles
 */
gulp.task('styles', () =>
    gulp.src(styles_src)
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', sass.logError))
        // .pipe(cleanCSS({format: 'beautify'}))
        // .pipe(gulp.dest(styles_dest))
        .pipe(cleanCSS())
        .pipe(rename({'suffix' : '.min'}))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest(styles_dest))
        .pipe(rtlcss())
        .pipe(rename((path) =>
            path.basename = path.basename.replace('.min', '-rtl.min')
        ))
        .pipe(gulp.dest(styles_dest))
)

/**
 * Build vendor assets
 */
gulp.task('vendor', () => {
    gulp.src(['./node_modules/@grottopress/scss/**'])
        .pipe(gulp.dest(`${vendor_assets}/@grottopress/scss`))
})

/**
 * Watch files for changes
 */
gulp.task('watch', () =>
    gulp.watch(styles_src, ['styles'])
)

/**
 * Default task
 */
gulp.task('default', [
    'styles',
    'watch'
])
