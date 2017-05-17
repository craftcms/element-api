Changelog
=========

## 2.1.0 - 2017-05-16

### Added
- Added support for a `resourceKey` endpoint config setting (default is `'data'`).

### Fixed
- Fixed the changelog and download URLs.

## 2.0.2 - 2017-04-18

### Fixed
- Fixed a PHP error that occurred when paginating results. ([#36](https://github.com/craftcms/element-api/issues/36))

## 2.0.1 - 2017-03-24

### Changed
- Craft 3 Beta 8 compatibility.

## 2.0.0 - 2017-02-10

### Added
- Craft 3 compatibility.
- It’s now possible to provide custom resource adapter classes, which could be associated with other things besides elements.
- Added `craft\elementapi\ResourceAdapterInterface`.
- Added `craft\elementapi\ElementResourceAdapter`.

### Deprecated
- Deprecated the `first` endpoint config setting. Use `one` instead.

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
