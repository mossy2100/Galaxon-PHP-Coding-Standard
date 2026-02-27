<?php

declare(strict_types=1);

namespace Galaxon\CodingStandard\Tests;

class DocblockInsideHookTest
{
    /** @var array<string, int> */
    private array $items = [];

    public float $multiplier {
        /**
         * Getter for multiplier.
         * This docblock is outdented inside the hook container.
         */
        get => array_product($this->items);
    }
}
