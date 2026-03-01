<?php

declare(strict_types=1);

namespace Galaxon\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Galaxon.Classes.PropertyDeclaration sniff.
 */
class PropertyDeclarationUnitTest extends AbstractSniffTestCase
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
            // VarUsed + ScopeMissing.
            61 => 2,
            // Multiple.
            64 => 1,
            // SpacingAfterType: 0 spaces.
            67 => 1,
            // SpacingAfterType: 2 spaces.
            70 => 1,
            // StaticBeforeVisibility.
            73 => 1,
            // ReadonlyBeforeVisibility.
            76 => 1,
            // AvizKeywordOrder.
            79 => 1,
            // FinalAfterVisibility.
            82 => 1,
            // AbstractAfterVisibility.
            90 => 1,
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
        return [
            // Underscore prefix.
            58 => 1,
        ];
    }
}
