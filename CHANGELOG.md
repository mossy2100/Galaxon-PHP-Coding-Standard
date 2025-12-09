# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2025-12-09

### Added

- Integrated Slevomat Coding Standard with 89 comprehensive sniffs covering:
  - Arrays (4 sniffs)
  - Attributes (5 sniffs)
  - Classes (18 sniffs)
  - Commenting (6 sniffs)
  - Control Structures (15 sniffs)
  - Exceptions (2 sniffs)
  - Files (1 sniff)
  - Functions (13 sniffs)
  - Namespaces (12 sniffs)
  - Operators (5 sniffs)
  - PHP (5 sniffs)
  - Strings (1 sniff)
  - Type Hints (11 sniffs)
  - Variables (3 sniffs)
- Added Squiz sniffs for variable naming and string quote usage
- Comprehensive README documentation listing all included sniffs with descriptions
- Tests directory structure

### Changed

- Replaced custom `Galaxon.NamingConventions.ValidVariableName` with `Squiz.NamingConventions.ValidVariableName`
- Moved `php_codesniffer` from dev dependencies to runtime dependencies
- Updated README structure:
  - Separated "Custom Sniffs" section for Galaxon-specific sniffs
  - Created dedicated "Variable and Property Naming Convention" section
  - Listed all PSR-12, Squiz, and Slevomat sniffs with concise descriptions
- Renamed ClassInstantiationNoBracketsSniff error code from `UnnecessaryParentheses` to `NewWithUnnecessaryParentheses`
- Updated composer scripts: added `-vp` flags to `fix` command for verbose progress output

### Removed

- Custom `Galaxon.NamingConventions.ValidVariableNameSniff` (moved to _dev directory)

## [0.1.0] - 2025-11-23

### Added

- Initial release
- `Galaxon.NamingConventions.ValidVariableName` sniff - enforces `$lowerCamelCase` variable naming without leading underscores
- `Galaxon.Classes.ClassInstantiationNoBrackets` sniff - removes unnecessary parentheses around class instantiation (PHP 8.4+)
- Extends PSR-12 coding standard
- Auto-registration via `dealerdirect/phpcodesniffer-composer-installer`
