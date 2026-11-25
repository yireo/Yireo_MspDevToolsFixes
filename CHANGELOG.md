# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.3] - 25 November 2026
### Removed
- Support for PHP 8.1 (PHP 8.2 up to 8.5 is now supported)

### Fixed
- MSP DevTools could become active outside developer mode or for any visitor; it now requires developer mode and a valid, non-match-all IP allowlist entry
- AJAX/JSON requests were no longer excluded from injection because the old guard hooked into a removed method; replaced by a JSON request injection rule (requires msp/devtools ^1.2.16)
- DevTools script could break pages, be injected into non-HTML responses or be cached; it is now injected only before </body> in HTML responses, safely encoded, with no-cache headers and without breaking the page on errors
- Sensitive request values were exposed in the DevTools payload; these are now redacted
- CSP nonce was created too late for the CSP header; it is now generated before the header is rendered
- Response plugins are now scoped to the frontend and adminhtml areas
- Missing composer and module dependencies are now declared

## [1.0.2] - 22 October 2025
### Fixed
- Add 8.4
- Add dev keyword

## [1.0.1] - 25 April 2025
### Added
- CSP nonce

## [1.0.0] - 26 September 2024
### Added
- Official release
