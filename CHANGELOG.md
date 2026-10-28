# Change Log

## [Unreleased]
### Added
- loadViews() - structure loaded by getStructure() contains the names of views (from `INFORMATION_SCHEMA.VIEWS` (schema `public`)), needed by `viewExists()` in migrations

### Changed
- requires `efabrica/phoenix-core` ^4.1 (`StructureBehavior::loadViews()`)
- phpunit.xml.dist migrated to the PHPUnit 9 configuration schema (`<coverage>` instead of the deprecated `<filter><whitelist>`), the "deprecated schema" warning is gone

## [1.0.0] - 2026-10-26
### Added
- Added support for PHP 8.6
- PostgreSQL adapter (PgsqlAdapter, PgsqlQueryBuilder, PgsqlAdapterFactory) extracted from `lulco/phoenix` into this package, the shared PDO base stays in `efabrica/phoenix-pdo`

[unreleased]: https://github.com/efabrica-team/phoenix-pgsql/compare/1.0.0...HEAD
[1.0.0]: https://github.com/efabrica-team/phoenix-pgsql/compare/fd1da5b60160738b77af96bd3137f9766c77bf31...1.0.0
