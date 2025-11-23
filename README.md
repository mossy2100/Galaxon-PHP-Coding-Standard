# Galaxon PHP Coding Standard

A PHP_CodeSniffer coding standard for Galaxon PHP libraries.

## Features

- Extends PSR-12
- Enforces `$lowerCamelCase` naming (no leading underscores) for variables, parameters, and properties.
- Removes unnecessary parentheses around class instantiation for PHP 8.4+, changing `(new Foo())->member` to `new Foo()->member`. Works for properties and methods.

## Installation

```bash
composer require --dev galaxon/coding-standard
```

The standard is automatically registered with PHP_CodeSniffer via the `dealerdirect/phpcodesniffer-composer-installer` plugin.

## Usage

Create a `phpcs.xml` file in your project root:

```xml
<?xml version="1.0"?>
<ruleset name="My Project">
    <description>Coding standard for my project</description>

    <file>src</file>
    <file>tests</file>

    <rule ref="Galaxon"/>
</ruleset>
```

Then run:

```bash
vendor/bin/phpcs
vendor/bin/phpcbf  # Auto-fix issues
```

## Included Sniffs

### Galaxon.NamingConventions.ValidVariableName

Ensures all variables use `$lowerCamelCase` format without leading underscores.

```php
// Good
$userName = 'John';
$orderTotal = 100;

// Bad
$user_name = 'John';   // snake_case
$_private = 'value';   // leading underscore
```

### Galaxon.Classes.ClassInstantiationNoBrackets

Removes unnecessary parentheses around `new` expressions when accessing members (PHP 8.4+).

```php
// Good (PHP 8.4+)
new DateTime()->format('Y-m-d');

// Bad (unnecessary parentheses)
(new DateTime())->format('Y-m-d');
```

## Requirements

- PHP 8.4+
- PHP_CodeSniffer 4.0+

## License

MIT
