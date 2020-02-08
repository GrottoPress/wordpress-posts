'use strict'

const { dest, series, src, watch } = require('gulp')

const cssnano = require('cssnano')
const filter = require('gulp-filter')
const newer = require('gulp-newer')
const postcss = require('gulp-postcss')
const rename = require('gulp-rename')
const rtlcss = require('gulp-rtlcss')
const sass = require('gulp-sass')
const sh = require('shelljs')

const paths = {
    styles: {
        dest: './dist/styles',
        mapDest: '.',
        src: ['./src/assets/styles/*.scss'],
        watchSrc: ['./src/assets/styles/**/*.scss']
    },
    vendor: {
        dest: {
            assets: './src/assets/vendor'
        }
    }
}

function _chmod(done)
{
    sh.chmod('-R', 'a+x', './bin', './vendor/bin', './node_modules/.bin')

    done()
}

function _clean(done)
{
    sh.rm('-rf', paths.styles.dest, paths.vendor.dest.assets)

    done()
}

function _styles(done)
{
    src(paths.styles.src, {sourcemaps: true})
        .pipe(newer(paths.styles.dest))
        .pipe(sass().on('error', sass.logError))
        .pipe(postcss([cssnano()]))
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
    watch(paths.styles.watchSrc, {ignoreInitial: false}, _styles)

    done()
}

exports.chmod = _chmod
exports.clean = _clean
exports.styles = _styles
exports.vendor = _vendor
exports.watch = _watch

exports.default = series(_styles, _watch)
