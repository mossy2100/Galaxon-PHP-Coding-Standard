<?php

// === VALID: Simple list arrays ===

// Valid: simple list on one line.
$a = ['red', 'green', 'blue'];

// Valid: empty array.
$b = [];

// === ERRORS: Simple list arrays ===

// ListTrailingComma: trailing comma in simple list.
$c = ['red', 'green', 'blue',];

// ListShouldBeSingleLine: multi-line simple list that fits on one line.
$d = [
    'red',
    'green',
    'blue',
];

// === VALID: List of arrays ===

// Valid: list of arrays, one per line, trailing comma.
$e = [
    [1, 2],
    [3, 4],
    [5, 6],
];

// === ERRORS: List of arrays ===

// ListOfArraysMissingTrailingComma: missing trailing comma.
$f = [
    [1, 2],
    [3, 4],
    [5, 6]
];

// ListOfArraysFirstElementNewLine: first element on same line as bracket.
$g = [[1, 2],
    [3, 4],
    [5, 6],
];

// ListOfArraysElementNewLine: elements on same line.
$h = [
    [1, 2], [3, 4],
    [5, 6],
];

// ListOfArraysClosingBracketNewLine: closing bracket on same line as last element.
$i = [
    [1, 2],
    [3, 4],
    [5, 6],];

// ListOfArraysElementIndent: wrong indentation.
$j = [
        [1, 2],
    [3, 4],
    [5, 6],
];

// ListOfArraysClosingBracketIndent: wrong indentation on closing bracket.
$k = [
    [1, 2],
    [3, 4],
    [5, 6],
        ];

// All on one line: first element, elements, closing bracket on same line + missing trailing comma.
$l2 = [[1, 2], [3, 4], [5, 6]];

// === VALID: Associative arrays ===

// Valid: associative array, one pair per line, aligned arrows, trailing comma.
$l = [
    'name'  => 'John',
    'email' => 'john@example.com',
    'age'   => 30,
];

// Valid: explicit sequential integer keys treated as associative.
$l3 = [
    0 => 'red',
    1 => 'green',
    2 => 'blue',
];

// Valid: mixed keyed and unkeyed elements, arrows aligned.
$l4 = [
    'first',
    'key'   => 'value',
    'second',
    'other' => 'thing',
];

// === ERRORS: Associative arrays ===

// AssocMissingTrailingComma: missing trailing comma.
$m = [
    'name'  => 'John',
    'email' => 'john@example.com',
    'age'   => 30
];

// AssocFirstElementNewLine: first element on same line as bracket.
$n = ['name'  => 'John',
    'email' => 'john@example.com',
    'age'   => 30,
];

// AssocElementNewLine: elements on same line.
$o = [
    'name' => 'John', 'email' => 'john@example.com',
    'age'  => 30,
];

// AssocClosingBracketNewLine: closing bracket on same line as last element.
$p = [
    'name'  => 'John',
    'email' => 'john@example.com',
    'age'   => 30,];

// AssocArrowAlignment: arrows not aligned.
$q = [
    'name' => 'John',
    'email' => 'john@example.com',
    'age' => 30,
];

// AssocElementIndent: wrong indentation.
$r = [
        'name'  => 'John',
    'email' => 'john@example.com',
    'age'   => 30,
];

// AssocClosingBracketIndent: wrong indentation on closing bracket.
$s = [
    'name'  => 'John',
    'email' => 'john@example.com',
    'age'   => 30,
        ];

// AssocValueNotOnArrowLine: value on next line after arrow.
$t = [
    'name' =>
        'John',
    'age'  => 30,
];

// === VALID: Grid format ===

// Valid: grid format with uniform padding.
$u = [
    'red',     'green',   'blue',    'cyan',    'magenta', 'yellow',  'black',   'white',   'orange',  'purple',
    'brown',   'pink',    'grey',    'navy',    'teal',
];

// Valid: negative numbers that fit on one line (no grid needed).
$v = [-100, -200, -300, -400, -500, -600];

// === VALID: Non-scalar lists ===

// Valid: variables and expressions that fit on one line.
$w3 = [$alpha, $beta, $gamma];

// Valid: function call list that fits on one line.
$w2 = [strlen('hi'), ucfirst('bye'), trim('x')];

// Valid: new expression list that fits on one line.
$w4 = [new stdClass(), new stdClass(), new stdClass()];

// === ERRORS: One per line (non-scalar lists too long for single line) ===

// ListShouldBeOnePerLine: function call list too long for single line.
$w = [strtoupper('red'), strtolower('GREEN'), trim('  blue  '), substr('yellow', 0, 3), str_repeat('x', 10), ucfirst('test')];

// === ERRORS: Grid format ===

// ListShouldBeGrid: one-per-line list that should be grid.
$x = [
    'red',
    'green',
    'blue',
    'cyan',
    'magenta',
    'yellow',
    'black',
    'white',
    'orange',
    'purple',
    'brown',
    'pink',
    'grey',
    'navy',
    'teal',
];

// ListShouldBeGrid: single long line that should be grid.
$y = ['apple', 'banana', 'cherry', 'date', 'elderberry', 'fig', 'grape', 'honeydew', 'kiwi', 'lemon', 'mango', 'nectarine', 'orange', 'papaya'];

// ListShouldBeGrid: grid with wrong padding.
$z = [
    'red', 'green', 'blue', 'cyan', 'magenta', 'yellow', 'black', 'white', 'orange', 'purple',
    'brown', 'pink', 'grey', 'navy', 'teal',
];
