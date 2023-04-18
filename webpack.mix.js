let mix = require('laravel-mix')
let path = require('path')
let rtlcss = require('rtlcss')

mix.setResourceRoot('../')
mix.setPublicPath(path.resolve('./'))

mix.webpackConfig({
  watchOptions: { ignored: [
    path.posix.resolve(__dirname, './node_modules'),
    path.posix.resolve(__dirname, './dist'),
  ] }
})

// mix.js('src/js/posts.js', 'dist/js')

mix.postCss('src/css/posts.css', 'dist/css').sourceMaps()

mix.postCss(
    'src/css/posts-rtl.css',
    'dist/css',
    undefined,
    [rtlcss()]
).sourceMaps()

if (mix.inProduction()) {
  mix.version()
} else {
  Mix.manifest.refresh = _ => void 0
}