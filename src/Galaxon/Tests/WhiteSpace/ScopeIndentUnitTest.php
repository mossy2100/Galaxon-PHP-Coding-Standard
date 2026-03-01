<?php

declare(strict_types=1);

namespace Galaxon\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Galaxon.WhiteSpace.ScopeIndent sniff.
 *
 * Focuses on PHP 8.4 property hook indentation, which is the reason this
 * sniff was forked from Generic.WhiteSpace.ScopeIndent.
 */
class ScopeIndentUnitTest extends AbstractSniffTestCase
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
            // Hook body under-indented.
            89 => 1,
            // Code after hooks over-indented.
            94 => 1,
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
