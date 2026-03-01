<?php

declare(strict_types=1);

namespace Galaxon\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Galaxon.Classes.ClassInstantiationNoBrackets sniff.
 */
class ClassInstantiationNoBracketsUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        return [
            // Basic method call.
            44 => 1,
            // Property access.
            47 => 1,
            // Nullsafe operator.
            50 => 1,
            // Constructor with arguments.
            53 => 1,
            // Chained calls.
            56 => 1,
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        return [];
    }
}
