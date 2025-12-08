# Galaxon PHP Coding Standard

A PHP_CodeSniffer coding standard for Galaxon PHP libraries extending PSR-12 with custom rules.

**[License](LICENSE)** | **[Changelog](CHANGELOG.md)**

![PHP 8.4](docs/logo_php8_4.png)

## Description

This package provides a custom PHP_CodeSniffer coding standard for Galaxon PHP libraries. It extends PSR-12 with additional rules for consistent naming conventions and modern PHP 8.4+ syntax.

**Key Features:**
- Extends PSR-12 coding standard
- Enforces `$lowerCamelCase` naming for variables, parameters, and properties
- Removes unnecessary parentheses around class instantiation (PHP 8.4+)
- Automatic registration with PHP_CodeSniffer

## Development and Quality Assurance / AI Disclosure

[Claude Chat](https://claude.ai) and [Claude Code](https://www.claude.com/product/claude-code) were used in the development of this package. The core classes were designed, coded, and commented primarily by the author, with Claude providing substantial assistance with code review, suggesting improvements, debugging, and generating tests and documentation. All code was thoroughly reviewed by the author, and validated using industry-standard tools including [PHP_Codesniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/) and [PHPStan](https://phpstan.org/) (to level 9) to ensure full compliance with [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards. This collaborative approach resulted in a high-quality, thoroughly-tested, and well-documented package delivered in significantly less time than traditional development methods.

## Requirements

- PHP ^8.4
- squizlabs/php_codesniffer ^4.0

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
vendor/bin/phpcs        # Check for issues
vendor/bin/phpcbf       # Auto-fix issues
```

## Sniffs

### Galaxon.NamingConventions.ValidVariableName

Ensures all variables, parameters, and properties use `$lowerCamelCase` format without leading underscores.

**Good:**
```php
$userName = 'John';
$orderTotal = 100;
$isValid = true;
```

**Bad:**
```php
$user_name = 'John';   // snake_case not allowed
$_private = 'value';   // leading underscore not allowed
$UserName = 'John';    // UpperCamelCase not allowed
```

PSR-12 and PER 3.0 do not mandate variable naming conventions. Specifically, from [PSR-1, Section 4.2 "Properties"](https://www.php-fig.org/psr/psr-1/#42-properties):

> This guide intentionally avoids any recommendation regarding the use of \$StudlyCaps, \$camelCase, or \$under_score property names.
> Whatever naming convention is used SHOULD be applied consistently within a reasonable scope. That scope may be vendor-level, package-level, class-level, or method-level.

In addition, from [PER Coding Style 3.0 Section 4.3 "Properties and Constants"](https://www.php-fig.org/per/coding-style/#43-properties-and-constants):
> Property or constant names MUST NOT be prefixed with a single underscore to indicate protected or private visibility. That is, an underscore prefix explicitly has no meaning.

Once upon a time, the convention was to use `lower_snake_case` for variable names and properties; however, as the object-oriented features of PHP evolved, it became more common to use `lowerCamelCase`, following the coding convention from Java. AI-generated code typically uses `lowerCamelCase`, which is indicative of the trend. Therefore, given the requirement to be consistent, this sniff enforces the use of `lowerCamelCase` for all variables, class properties, and function parameters.

Similarly, using an underscore prefix to indicate protected or private visibility was common practice in PHP until use of visibility modifiers became the standard. And now, the use of an underscore prefix is generally discouraged or disallowed.

This sniff is compliant with several PHP coding standards:
1. Symfony requires `lowerCamelCase` ([ref](https://symfony.com/doc/current/contributing/code/standards.html#naming-conventions)).
2. Laravel requires `lowerCamelCase` ([unofficially](https://spatie.be/guidelines/laravel-php#content-general-php-rules)).
3. Drupal variable names may use either `lowerCamelCase` or `lower_snake_case` ([ref](https://project.pages.drupalcode.org/coding_standards/php/coding/#functions-and-variables)), as long as one is consistent. Properties should use `lowerCamelCase`, and protected or private properties should not use an underscore prefix. ([ref](https://project.pages.drupalcode.org/coding_standards/php/coding/#classes-methods-and-properties)).

Therefore, on the off chance any of the Galaxon packages are used in projects based on these frameworks, the code should be compliant.

### Galaxon.Classes.ClassInstantiationNoBrackets

Removes unnecessary parentheses around `new` expressions when accessing members (PHP 8.4+).

PHP 8.4 introduced the ability to access properties and methods on newly instantiated objects without wrapping the instantiation in parentheses. This sniff enforces that modern syntax.

**Good:**
```php
new DateTime()->format('Y-m-d');
new Foo()->method();
new Bar()->property;
```

**Bad:**
```php
(new DateTime())->format('Y-m-d');  // Unnecessary parentheses
(new Foo())->method();              // Unnecessary parentheses
(new Bar())->property;              // Unnecessary parentheses
```

## License

MIT License - see [LICENSE](LICENSE) for details

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

For questions or suggestions, please [open an issue](https://github.com/mossy2100/PHP-CodingStandard/issues).

## Support

- **Issues**: https://github.com/mossy2100/PHP-CodingStandard/issues
- **Examples**: See `phpcs.xml` files in other Galaxon packages

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.
