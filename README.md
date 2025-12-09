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

## Included Sniffs

The Galaxon coding standard extends PSR-12 and includes the following additional sniffs:

### Base Standard

- **PSR12**: Complete PSR-12 coding standard (with exception for multiple classes in test files)

### Galaxon Custom Sniffs

- **Galaxon.Classes.ClassInstantiationNoBrackets**: Removes unnecessary parentheses around class instantiation when accessing members (PHP 8.4+)

### Squiz Sniffs

- **Squiz.NamingConventions.ValidVariableName**: Enforces lowerCamelCase for variables, parameters, and properties
- **Squiz.Strings.DoubleQuoteUsage.NotRequired**: Ensures strings use single quotes unless double quotes are necessary

### Slevomat Sniffs

#### Arrays
- **ArrayAccess**: Disallows whitespace between array access operators
- **DisallowImplicitArrayCreation**: Disallows implicit array creation
- **DisallowPartiallyKeyed**: Requires arrays to have keys for all or none of the values
- **SingleLineArrayWhitespace**: Checks whitespace in single-line array declarations

#### Attributes
- **AttributeAndTargetSpacing**: Enforces spacing between attributes and their targets
- **AttributesOrder**: Requires attributes to be ordered alphabetically
- **DisallowAttributesJoining**: Disallows joining multiple attributes with commas
- **DisallowMultipleAttributesPerLine**: Requires each attribute on its own line
- **RequireAttributeAfterDocComment**: Requires attributes to appear after doc comments

#### Classes
- **BackedEnumTypeSpacing**: Enforces spacing around backed enum types
- **ClassConstantVisibility**: Requires visibility modifiers on class constants
- **ClassMemberSpacing**: Enforces spacing between class members
- **ConstantSpacing**: Enforces spacing around class constants
- **DisallowMultiConstantDefinition**: Disallows defining multiple constants in one statement
- **DisallowMultiPropertyDefinition**: Disallows defining multiple properties in one statement
- **DisallowStringExpressionPropertyFetch**: Disallows string expressions for property access
- **EmptyLinesAroundClassBraces**: Enforces no empty lines after opening or before closing class braces
- **EnumCaseSpacing**: Enforces spacing between enum cases
- **MethodSpacing**: Enforces spacing between methods
- **ModernClassNameReference**: Requires ::class syntax for class name references
- **PropertyDeclaration**: Enforces proper property declaration format including promoted properties
- **PropertySpacing**: Enforces spacing between properties
- **RequireMultiLineMethodSignature**: Requires multi-line format for long method signatures
- **RequireSelfReference**: Requires self:: instead of ClassName:: for self-references
- **RequireSingleLineMethodSignature**: Requires single-line format for short method signatures
- **TraitUseDeclaration**: Enforces proper trait use declaration format
- **TraitUseSpacing**: Enforces spacing around trait use statements

#### Commenting
- **AnnotationName**: Enforces correct annotation names
- **DeprecatedAnnotationDeclaration**: Enforces proper @deprecated annotation format
- **EmptyComment**: Disallows empty comments
- **RequireOneLineDocComment**: Requires one-line format for short doc comments
- **RequireOneLinePropertyDocComment**: Requires one-line format for short property doc comments
- **UselessInheritDocComment**: Disallows useless @inheritDoc comments

#### Control Structures
- **AssignmentInCondition**: Disallows assignments in if, elseif, and do-while conditions
- **DisallowContinueWithoutIntegerOperandInSwitch**: Disallows continue without integer operand in switch
- **DisallowTrailingMultiLineTernaryOperator**: Requires leading operators in multi-line ternary expressions
- **LanguageConstructWithParentheses**: Requires parentheses for language constructs
- **NewWithParentheses**: Requires parentheses with new keyword
- **RequireMultiLineCondition**: Requires multi-line format for long conditions with boolean operators
- **RequireMultiLineTernaryOperator**: Requires multi-line format for long ternary operators
- **RequireNullCoalesceEqualOperator**: Requires ??= operator when possible
- **RequireNullCoalesceOperator**: Requires ?? operator when possible
- **RequireNullSafeObjectOperator**: Requires ?-> operator when possible
- **RequireSingleLineCondition**: Requires single-line format for short conditions
- **RequireShortTernaryOperator**: Requires short ternary operator ?: when possible
- **RequireTernaryOperator**: Requires ternary operator when possible
- **DisallowYodaComparison**: Disallows Yoda comparisons (constant === $variable)
- **UselessIfConditionWithReturn**: Disallows useless if conditions returning true or false

#### Exceptions
- **DeadCatch**: Disallows empty catch blocks that don't handle exceptions
- **RequireNonCapturingCatch**: Requires catch without variable when exception is not used

#### Files
- **LineLength**: Enforces maximum line length of 120 characters

#### Functions
- **ArrowFunctionDeclaration**: Enforces proper arrow function declaration format
- **DisallowEmptyFunction**: Disallows empty function bodies
- **FunctionLength**: Enforces maximum function length of 100 lines
- **RequireArrowFunction**: Requires arrow functions for simple closures
- **RequireMultiLineCall**: Requires multi-line format for long function calls
- **RequireSingleLineCall**: Requires single-line format for short function calls
- **NamedArgumentSpacing**: Enforces spacing around named arguments
- **DisallowTrailingCommaInCall**: Disallows trailing comma in function calls
- **DisallowTrailingCommaInClosureUse**: Disallows trailing comma in closure use statements
- **DisallowTrailingCommaInDeclaration**: Disallows trailing comma in function declarations
- **StaticClosure**: Requires static keyword for closures that don't use $this
- **StrictCall**: Enforces strict call_user_func_array and call_user_func usage
- **UselessParameterDefaultValue**: Disallows useless parameter default values

#### Namespaces
- **AlphabeticallySortedUses**: Requires use statements to be alphabetically sorted
- **DisallowGroupUse**: Disallows group use declarations
- **MultipleUsesPerLine**: Disallows multiple use statements on one line
- **NamespaceDeclaration**: Enforces proper namespace declaration format
- **NamespaceSpacing**: Enforces spacing around namespace declarations
- **ReferenceUsedNamesOnly**: Requires use statements for all referenced names
- **RequireOneNamespaceInFile**: Requires exactly one namespace per file
- **UnusedUses**: Disallows unused use statements including in annotations
- **UseDoesNotStartWithBackslash**: Disallows leading backslash in use statements
- **UseFromSameNamespace**: Disallows use statements from the same namespace
- **UseSpacing**: Enforces spacing between use statements
- **UselessAlias**: Disallows useless use statement aliases

#### Operators
- **DisallowEqualOperators**: Disallows == and != operators, requires === and !==
- **NegationOperatorSpacing**: Enforces spacing around negation operator
- **RequireCombinedAssignmentOperator**: Requires combined assignment operators (+=, -=, etc.)
- **RequireOnlyStandaloneIncrementAndDecrementOperators**: Requires ++ and -- to be used standalone
- **SpreadOperatorSpacing**: Enforces spacing around spread operator

#### PHP
- **DisallowDirectMagicInvokeCall**: Disallows direct __invoke() calls
- **ReferenceSpacing**: Enforces spacing around reference operator
- **ShortList**: Requires short list syntax []
- **TypeCast**: Enforces proper type cast format
- **UselessSemicolon**: Disallows useless semicolons

#### Strings
- **DisallowVariableParsing**: Disallows variable parsing in strings

#### Type Hints
- **DeclareStrictTypes**: Requires declare(strict_types=1)
- **ParameterTypeHintSpacing**: Enforces spacing around parameter type hints
- **ReturnTypeHintSpacing**: Enforces spacing around return type hints
- **LongTypeHints**: Requires long type hints (int instead of integer)
- **DNFTypeHintFormat**: Enforces DNF type format without spaces
- **NullableTypeForNullDefaultValue**: Requires nullable type for parameters with null default
- **ParameterTypeHint**: Requires parameter type hints
- **PropertyTypeHint**: Requires property type hints
- **ReturnTypeHint**: Requires return type hints
- **UselessConstantTypeHint**: Disallows useless constant type hints
- **ClassConstantTypeHint**: Requires class constant type hints

#### Variables
- **DisallowVariableVariable**: Disallows variable variables ($$var)
- **DuplicateAssignmentToVariable**: Disallows duplicate assignments to the same variable
- **UselessVariable**: Disallows useless variables

## Variable and Property Naming Convention

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


## Custom Sniffs

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
