# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2025-11-23

### Added

- Initial release
- `Galaxon.NamingConventions.ValidVariableName` sniff - enforces `$lowerCamelCase` variable naming without leading underscores
- `Galaxon.Classes.ClassInstantiationNoBrackets` sniff - removes unnecessary parentheses around class instantiation (PHP 8.4+)
- Extends PSR-12 coding standard
- Auto-registration via `dealerdirect/phpcodesniffer-composer-installer`
