<?php

declare(strict_types=1);

namespace Test;

// Parentheses around new
$a = new DateTime()->format('Y-m-d');

// Parentheses around string
$b = ('hello');

// Parentheses around variable
$c = ($a);

// Parentheses around operation
$d = (1 + 2);

// Parentheses in ternary
$e = ($x > 5) ? 'yes' : 'no';
