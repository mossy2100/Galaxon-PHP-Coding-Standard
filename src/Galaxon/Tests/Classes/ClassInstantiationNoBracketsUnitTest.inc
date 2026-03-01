<?php

class Foo
{
    public string $name = 'foo';

    public function method(): string
    {
        return 'bar';
    }
}

class Bar
{
    public function method(): string
    {
        return 'baz';
    }
}

// === VALID: No unnecessary parentheses ===

// Valid: already unwrapped (PHP 8.4+ syntax).
$a = new Foo()->method();

// Valid: no member access after parentheses.
$b = (new Foo());

// Valid: expression inside parentheses (ternary), not just new.
$c = ($condition ? new Foo() : new Bar())->method();

// Valid: simple instantiation, no parentheses at all.
$d = new Foo();

// Valid: instantiation with arguments, no member access.
$e = (new Foo());

// Valid: parentheses used in arithmetic, not member access.
$f = (new Foo()) + 1;

// === ERRORS: Unnecessary parentheses ===

// NewWithUnnecessaryParentheses: basic method call.
$g = (new Foo())->method();

// NewWithUnnecessaryParentheses: property access.
$h = (new Foo())->name;

// NewWithUnnecessaryParentheses: nullsafe operator.
$i = (new Foo())?->method();

// NewWithUnnecessaryParentheses: constructor with arguments.
$j = (new Foo(1, 2))->method();

// NewWithUnnecessaryParentheses: chained calls.
$k = (new Foo())->method()->name;
