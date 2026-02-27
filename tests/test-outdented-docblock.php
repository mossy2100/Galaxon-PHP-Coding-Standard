<?php

declare(strict_types=1);

namespace Galaxon\CodingStandard\Tests;

class OutdentedDocblockTest
{
    private string $backing = 'value';

    /**
     * This docblock is outdented - should be at 4 spaces.
     * Second line of docblock.
     */
    public string $hooked {
        get => $this->backing;
    }
}
