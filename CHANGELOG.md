# Release Notes for Element API

## 1.6.0 - 2017-05-25

### Added
- Added the `include` and `exclude` endpoint config settings. ([#41](https://github.com/craftcms/element-api/pull/41))

## 1.5.0 - 2017-05-24

### Added
- Added the `cache` endpoint config setting.

### Fixed
- Fixed a bug where endpoint were sending JSON headers even if an exception occurred and the HTML error view was returned. ([#39](https://github.com/craftcms/element-api/issues/39))

## 1.4.0 - 2017-05-18

### Added
- Added a [JSON Feed V1](https://jsonfeed.org/version/1) serializer.
- Added the `meta` endpoint config setting.
- Added the `serializer` endpoint config setting.
- Added the `jsonOptions` endpoint config setting.

### Changed
- Updated Fractal to 0.16.

## 1.3.0 - 2017-05-16

### Added
- Added support for a `resourceKey` endpoint config setting (default is `'data'`).

## 1.2.1 - 2016-04-12

### Changed
- Pagination URLs will now honor any existing query string parameters.

## 1.2.0 - 2016-04-02

### Added
- Added the `elementApi.onBeforeSendData` event.

## 1.1.0 - 2015-12-20

### Changed
- Updated to take advantage of new Craft 2.5 plugin features.

## 1.0.0 - 2015-08-19

Initial release.
