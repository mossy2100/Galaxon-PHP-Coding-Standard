<?php

declare(strict_types=1);

// Test unnecessary parentheses around new
$formatted = new DateTimeImmutable()->format('Y-m-d');
$result = new DateTime()->modify('+1 day');
echo $result->format('Y-m-d'), PHP_EOL;
$value = new stdClass()->property;

$result = new DateTime()->modify('+1 day');
echo $result->format('Y-m-d'), PHP_EOL;

$result = new DateTime();
echo $result->format('Y-m-d'), PHP_EOL;
