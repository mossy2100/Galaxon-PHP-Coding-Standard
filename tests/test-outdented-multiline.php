<?php

declare(strict_types=1);

namespace Test;

class Foo
{
    private int $x = 0;

    /**
     * Multiplier docblock.
     *
     * Second paragraph.
     */
    public float $multiplier {
        get => 1.0;
    }
}
