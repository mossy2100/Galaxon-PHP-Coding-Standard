<?php

declare(strict_types=1);

namespace Galaxon\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Galaxon.Arrays.ArrayDeclaration sniff.
 */
class ArrayDeclarationUnitTest extends AbstractSniffTestCase
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
            // Simple list: trailing comma.
            14  => 1,
            // Simple list: should be single line (trailing comma handled by fix).
            17  => 1,
            // List of arrays: missing trailing comma.
            38  => 1,
            // List of arrays: first element on same line.
            42  => 1,
            // List of arrays: element on same line.
            49  => 1,
            // List of arrays: closing bracket on same line.
            57  => 1,
            // List of arrays: wrong indentation.
            61  => 1,
            // List of arrays: wrong closing bracket indentation.
            71  => 1,
            // List of arrays: all on one line.
            74  => 5,
            // Assoc: missing trailing comma.
            106 => 1,
            // Assoc: first element on same line.
            110 => 1,
            // Assoc: element on same line + arrow alignment.
            117 => 2,
            118 => 1,
            // Assoc: closing bracket on same line.
            125 => 1,
            // Assoc: arrow alignment.
            129 => 1,
            131 => 1,
            // Assoc: wrong indentation.
            136 => 1,
            // Assoc: wrong closing bracket indentation.
            146 => 1,
            // Assoc: value not on arrow line.
            151 => 1,
            // One per line: function call list too long for single line.
            180 => 1,
            // Grid: one-per-line list that should be grid.
            185 => 1,
            // Grid: single long line that should be grid.
            204 => 1,
            // Grid: grid with wrong padding.
            207 => 1,
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
