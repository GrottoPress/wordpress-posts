# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

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
