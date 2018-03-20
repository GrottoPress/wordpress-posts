'use strict'

const gulp = require('gulp')
const rename = require('gulp-rename')
const rtlcss = require('gulp-rtlcss')
const cleanCSS = require('gulp-clean-css')
const sass = require('gulp-sass')
const sourcemaps = require('gulp-sourcemaps')

const styles_src = ['./assets/styles/**/*.scss']
const styles_dest = './dist/styles'
const vendor_assets = './assets/vendor'

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

gulp.task('vendor', () => {
    gulp.src(['./node_modules/@grottopress/scss/**'])
        .pipe(gulp.dest(`${vendor_assets}/@grottopress/scss`))
})

gulp.task('watch', () =>
    gulp.watch(styles_src, ['styles'])
)

gulp.task('default', [
    'styles',
    'watch'
])
