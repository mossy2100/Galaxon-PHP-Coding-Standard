<?php

declare(strict_types=1);

namespace Galaxon\CodingStandard\Tests;

class MultilineDocblockTest
{
    /** @var array<string, int> */
    private array $items = [];

    /**
     * The combined multiplier from all items.
     *
     * This is the product of each item's value raised to its exponent.
     * For example, km²⋅ms⁻¹ would have multiplier 1000² × 0.001⁻¹ = 1e9.
     */
    public float $multiplier {
        get => array_product($this->items);
    }
}
