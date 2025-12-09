<?php

namespace Galaxon\CodingStandard\Tests;

// Test 1: FQN without use statement (should trigger Slevomat)
$date = new \DateTime();

// Test 2: snake_case variable (should trigger Squiz)
$my_variable = 'test';

// Test 3: Double quotes when not needed (should trigger Squiz). Auto-fixable.
$string = "hello";

// Test 4: Leading underscore variable (should trigger Squiz)
$_private = 'value';

// Test 5: Multiple FQNs
$now = new DateTime('now');
$immutable = new DateTimeImmutable();
$interval = new DateInterval('P1D');

// Test 6: Interpolated variables with underscores.
$string = "$my_variable $_private";
