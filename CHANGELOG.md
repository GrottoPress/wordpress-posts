# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased] -

### Added
- Add support for PHP 7.4

### Fixed
- Fix deprecation notice in codeception

## 0.14.1 - 2019-04-17

### Fixed
- Fix `src/` directory not installed when composer's `--prefer-dist` is used

## 0.14.0 - 2019-04-17

### Added
- Add PHP 7.3 to travis-ci build matrix
- Add `.gitattributes`
- Set up [WP-CLI](https://packagist.org/packages/wp-cli/wp-cli-bundle)

### Changed
- Update gulp to version 4

### Removed
- Remove support for [Google+](https://blog.google/technology/safety-security/project-strobe/)

## 0.13.0 - 2018-10-06

### Changed
- Rename `LICENSE.md` to `LICENSE`
- Move `lib/`, `lang/`, `assets/` into a new `src/` directory

## 0.12.2

### Fixed
- Supply text domain argument to translation functions

## 0.12.1

### Fixed
- Supply text domain argument to translation functions

## 0.12.0 - 2018-09-11

### Added
- Add translations template

### Changed
- Rename `libraries/` directory to `lib/`

## 0.11.1 - 2018-08-22

### Fixed
- Update documentation to reflect previous release

## 0.11.0 - 2018-08-22

### Changed
- Move composing classes one level up for shorter namespaces

## 0.10.3 - 2018-07-27

### Fixed
- Reduced grid gutter not applied on small screens

## 0.10.2 - 2018-07-27

### Fixed
- Small breakpoint styles overriding medium breakpoint styles

### Changed
- Reduce grid posts gutter to `16px`

## 0.10.1 - 2018-07-27

### Added
- Set up [postcss](https://postcss.org/)

### Changed
- Update styles following `@grottopress/scss` upgrade

## 0.10.0 - 2018-07-23

### Added
- `.editorconfig`

### Changed
- Use `<small>` tag for posts info wrapper
- Reduce medium device breakpoint to **768px**
- Change breakpoints to `small: 600px`, `medium: 900px`.

## 0.9.2 - 2018-07-16

### Changed
- Set `border-box` for grid posts to `box-sizing`

### Fixed
- Ensure posts wrap class has only one `image-align-*` and `layout-*`

## 0.9.1 - 2018-05-04

### Changed
- Ensure avatar does not display if author not supported by post type.

## 0.9.0 - 2018-04-13

### Changed
- Move creation of mobile detector for `Post\Info` class to `Post\Post` class.

## 0.8.0 - 2018-03-22

### Added
- Unit tests

### Removed
- Shell script to copy assets from `node_modules` to `assets/vendor`

### Changed
- Replace WP browser with codeception for tests
- Update font awesome social icon class names to v5

## 0.7.2 - 2018-03-01

### Removed
- Redundant doc blocks/comments

## 0.7.1 - 2018-02-28

### Fixed
- Notice: "Undefined property: GrottoPress\WordPress\Post\Comments::$count"

## 0.7.0 - 2018-02-28

### Added
- `.security.txt`
- Travis-CI setup

### Changed
- Upgrade `grottopress/getter` package

## 0.6.0 - 2018-02-17

### Changed
- Major refactor: Rewrite `Post\Post` class and update other classes accordingly.

## 0.5.1 - 2018-02-02

### Changed
- Made classes extensible

## 0.5.0 - 2018-01-19

### Removed
- Reversed installing npm packages with `fxp/composer-asset-plugin` composer plugin

## 0.4.0 - 2018-01-18

### Changed
- Installed npm packages with composer's `fxp/composer-asset-plugin` instead of npm

## 0.3.6 - 2018-01-06

### Changed
- Allowed overriding SCSS variables

## 0.3.5 - 2018-01-05

### Changed
- Namespaced all stylesheet variable names

## 0.3.4 - 2018-01-05

### Changed
- Updated grid styles to be compatible with @grottopress/scss update

## 0.3.3 - 2017-12-30

### Added
- Allow publicly getting pagination key

## 0.3.2 - 2017-12-30

### Fixed
- Fixed 404 errors when using `$wp_rewrite->pagination_base` as pagination key

## 0.3.1 - 2017-12-09

## Changed
- Used grid mixin for grid posts styles.
- Fixed text not displaying for grid posts.

## 0.3.0 - 2017-12-08

### Changed

    .
    ├── assets
    ├── bin
    ├── dist
    ├── libraries
    ├── node_modules
    └── vendor

- New directory structure (above)
- Added development binaries in a new `bin` directory
- Set up tests scaffold

## 0.2.2 - 2017-11-26

### Changed
- Renamed `src/lib` to `src/libraries/`

## 0.2.1 - 2017-11-25

### Changed
- Thoroughly clean `$args['class']` argument before adding to markup

## 0.2.0 - 2017-11-17

### Added
- Added sourcemaps for minified CSS and JS

## Changed
- Using `GrottoPress\Getter\Getter` package to get object attributes.
- Not showing any posts if `posts_per_page` is `0`
- Converted all `==` and `!=` checks to their stricter `===` and `!==` equivalents.

## 0.1.1 - 2017-10-06

### Fixed
- Fixed incorrect attribute name when getting attributes from `\WP_Post`.
- Fixed `googleplus_link` post info not working.

## 0.1.0 - 2017-10-05

### Added
- Initial public release.
