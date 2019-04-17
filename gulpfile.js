'use strict'

const { src, dest, watch, series } = require('gulp')

const rename = require('gulp-rename')
const newer = require('gulp-newer')
const filter = require('gulp-filter')
const rtlcss = require('gulp-rtlcss')
const sass = require('gulp-sass')
const postcss = require('gulp-postcss')
const cssnano = require('cssnano')
const mqpacker = require('css-mqpacker')
const mqsort = require('sort-css-media-queries')

const paths = {
    styles: {
        src: ['./src/assets/styles/**/*.scss'],
        dest: './dist/styles',
        mapDest: '.'
    },
    vendor: {
        dest: {
            assets: './src/assets/vendor'
        }
    }
}

function _styles(done)
{
    src(paths.styles.src, {sourcemaps: true})
        .pipe(newer(paths.styles.dest))
        .pipe(sass().on('error', sass.logError))
        .pipe(postcss([mqpacker({sort: mqsort}), cssnano()]))
        .pipe(rename({'suffix': '.min'}))
        .pipe(dest(paths.styles.dest, {sourcemaps: paths.styles.mapDest}))
        .pipe(filter(['**/*.css']))
        .pipe(rtlcss())
        .pipe(rename(path =>
            path.basename = path.basename.replace('.min', '-rtl.min')
        ))
        .pipe(dest(paths.styles.dest, {sourcemaps: paths.styles.mapDest}))

    done()
}

function _vendor(done)
{
    src(['./node_modules/@grottopress/scss/**'])
        .pipe(newer(paths.vendor.dest.assets))
        .pipe(dest(`${paths.vendor.dest.assets}/@grottopress/scss`))

    done()
}

function _watch(done)
{
    watch(paths.styles.src, {ignoreInitial: false}, _styles)

    done()
}

exports.styles = _styles
exports.vendor = _vendor
exports.watch = _watch

exports.default = series(_styles, _watch)
