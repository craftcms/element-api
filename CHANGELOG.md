# Release Notes for Element API

## 4.1.0 - 2024-03-14

- Element API now requires Craft 4.3.0+ or 5.0.0+.
- Endpoints are no longer cached beyond the lowest expiry date in the results. ([#187](https://github.com/craftcms/element-api/issues/187))

## 4.0.0 - 2024-03-11

- Added Craft 5 compatibility. ([#186](https://github.com/craftcms/element-api/pull/186))
- Updated Fractal to 0.20. ([#183](https://github.com/craftcms/element-api/pull/183))

## 3.0.1.1 - 2022-07-07

### Fixed
- Fixed changelog.

## 3.0.1 - 2022-07-07

### Fixed
- Fixed an issue where `EVENT_BEFORE_SEND_DATA` wasn't returning data. ([#165](https://github.com/craftcms/element-api/issues/165))

## 3.0.0 - 2022-05-03

### Added
- Added Craft 4 compatibility.

### Removed
- Removed `craft\elementapi\resources\EntryResource`. ([#108](https://github.com/craftcms/element-api/issues/108))

## 2.8.5 - 2022-05-03

### Fixed
- Fixed an error that occurred on Live Preview requests, for endpoints with a `cache` key defined. ([#158](https://github.com/craftcms/element-api/issues/158))

## 2.8.4 - 2021-12-15

### Fixed
- Fixed a bug where an invalid response code would be returned if the `one` endpoint config setting was used and Craft could not find a matching element. ([#157](https://github.com/craftcms/element-api/issues/157)) 

## 2.8.3 - 2021-09-25

### Fixed
- Fixed a bug where endpoint callables weren’t getting called for `OPTIONS` requests. ([#156](https://github.com/craftcms/element-api/issues/156))

## 2.8.2 - 2021-09-03

### Fixed
- Fixed a bug where custom `cache` durations were getting ignored. ([#153](https://github.com/craftcms/element-api/issues/153))

## 2.8.1 - 2021-09-01

### Fixed
- Fixed an “Header may not contain more than a single header” error that could occur if a different exception had occurred. ([#115](https://github.com/craftcms/element-api/issues/115))
- Fixed a bug where most exceptions were resulting in 404 responses rather than 500s.
- Fixed a bug where the `cacheKey` setting would cause an error on non-cached endpoints. ([#152](https://github.com/craftcms/element-api/pull/152))

## 2.8.0 - 2021-08-31

### Added
- Added the `cacheKey` endpoint configuration setting. ([#145](https://github.com/craftcms/element-api/pull/145))
- Added the `contentType` endpoint configuration setting.

### Changed
- Element API now requires Craft 3.6 or later.
- API endpoints now send `X-Robots-Tag: none` headers. ([#124](https://github.com/craftcms/element-api/issues/124))
- `OPTIONS` requests now return an empty response. ([#128](https://github.com/craftcms/element-api/issues/128))
- JSON Feed endpoints now set the `version` to `https://jsonfeed.org/version/1.1`.
- JSON Feed endpoints now use the `application/feed+json` content type by default.
- Error responses no longer contain the exception message, unless the exception thrown was an instance of `yii\base\UserException`. ([#130](https://github.com/craftcms/element-api/issues/130))

### Fixed
- Fixed a bug where API endpoints were returning cached responses for Live Preview requests. ([#143](https://github.com/craftcms/element-api/issues/143))
- Fixed a bug where endpoints whose route params didn’t align with the endpoint arguments would return a misleading 404 message. ([#137](https://github.com/craftcms/element-api/issues/137))

## 2.7.0 - 2021-05-26

### Added
- It’s now possible to invalidate Element API caches via the Caches utility and the `invalidate-tags/element-api` command. ([#136](https://github.com/craftcms/element-api/issues/136))

### Changed
- Element API now requires Craft CMS 3.5 or later.
- Endpoint responses are now cached by default, and are invalidated automatically when relevant elements are saved or deleted.
- Exceptions thrown while resolving endpoints are now logged. ([#117](https://github.com/craftcms/element-api/issues/117))

### Fixed
- Fixed a bug where non-200 responses were getting cached. ([#130](https://github.com/craftcms/element-api/issues/130))

## 2.6.0 - 2019-08-01

### Added
- Added the `callback` endpoint config setting, which enables JSONP output. ([#96](https://github.com/craftcms/element-api/issues/96))

### Changed
- The `defaults` setting can now be set to a callable. ([#98](https://github.com/craftcms/element-api/pull/98))
- `criteria` endpoint settings can now specify an `offset`. ([#99](https://github.com/craftcms/element-api/issues/99)) 
- Updated Fractal to 0.18. ([#109](https://github.com/craftcms/element-api/issues/109))

### Deprecated
- Deprecated `craft\elementapi\resources\EntryResource`. ([#108](https://github.com/craftcms/element-api/issues/108))

### Fixed
- Fixed a bug where field types that return objects were getting converted to arrays by the default element transformer. Now their [serialized value](https://docs.craftcms.com/api/v3/craft-base-fieldinterface.html#method-serializevalue) is used instead. ([#75](https://github.com/craftcms/element-api/issues/75))

## 2.5.4 - 2018-07-29

### Changed
- The `generateTransformsBeforePageLoad` Craft config setting is now automatically enabled for all Element API endpoints. ([#81](https://github.com/craftcms/element-api/issues/81))

### Fixed
- Fixed a bug where it wasn’t possible to set `cache`, `serializer`, `jsonOptions`, `pretty`, `includes`, and `excludes` endpoint config options in the `defaults` array. ([#69](https://github.com/craftcms/element-api/pull/69))

## 2.5.3 - 2018-06-20

### Changed
- The cache key now takes into account the current site id. ([#76](https://github.com/craftcms/element-api/issues/76))

## 2.5.2- 2017-12-13

### Changed
- Loosened the Craft CMS version requirement to allow any 3.x version.

## 2.5.1 - 2017-11-09

### Changed
- PHP 7.2 compatibility. 

## 2.5.0 - 2017-10-31

### Added
- Added support for fetching entry drafts & versions.
- Added `craft\elementapi\resources\EntryResource`.

### Changed
- Exceptions are now represented as JSON responses, with `error.code` and `error.message` properties.
- Renamed `craft\elementapi\ElementResourceAdapter` to `craft\elementapi\resources\ElementResource`.
- `craft\elementapi\resources\ElementResource::getElementQuery()` and `getTransformer()` are now protected methods.

## 2.4.3 - 2017-09-14

### Fixed
- Fixed a deprecation error that occurred on Craft 3 Beta 25 and later. ([craftcms/cms#1983](https://github.com/craftcms/cms/issues/1983))

## 2.4.2 - 2017-08-17

### Changed
- Craft 3 Beta 24 compatibility.

## 2.4.1 - 2017-07-07

> {note} You will need to rename your Element API config file to `element-api.php` when updating to Craft 3 Beta 20+ and Element API 2.4.1+.

### Changed
- Craft 3 Beta 20 compatibility.

## 2.4.0 - 2017-05-25

### Added
- Added the `include` and `exclude` endpoint config settings. ([#42](https://github.com/craftcms/element-api/pull/42))

## 2.3.0 - 2017-05-24

### Added
- Added the `cache` endpoint config setting.

## 2.2.0 - 2017-05-18

### Added
- Added a [JSON Feed V1](https://jsonfeed.org/version/1) serializer.
- Added the `meta` endpoint config setting.
- Added the `serializer` endpoint config setting.
- Added the `jsonOptions` endpoint config setting.
- Added the `pretty` endpoint config setting.

### Changed
- Updated Fractal to 0.16.

### Fixed
- Fixed a bug where pagination URLs contained an extra `pattern` query param.

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
