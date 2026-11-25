# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 25 November 2026
### Fixed
- Mass reindex action checked a misspelled ACL resource (`Yireo_BackendIndexer::reindex`), so the module's own ACL setting had no effect; it now requires both `Magento_Indexer::index` and `Yireo_BackendReindexer::reindex`
- ACL resource is now nested under Index Management in the role editor and the grid action is hidden for admins without it
- Mass reindex action only accepts POST requests with a valid form key
- Only known indexer IDs are reindexed, with at most 5 per request (configurable via the DI argument `maxIndexersPerRequest`) to avoid long-running web requests
- Reindex errors no longer show exception details in the Admin Panel but are logged to `var/log/system.log`
- Add missing `psr/log` composer dependency

## [1.0.0] - 06 October 2026
### Fixed
- Fix type error in PHP 8.4+

## [0.0.3] - 29 July 2020
### Added
- Magento 2.4 compatibility

## [0.0.2] - Undocumented
- Initial changes

## [0.0.1] - Undocumented
- Initial release
