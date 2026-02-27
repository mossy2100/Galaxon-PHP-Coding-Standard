<?php

declare(strict_types=1);

namespace Galaxon\CodingStandard\Tests;

class SimpleHookTest
{
    private string $backing = 'value';

    public string $hooked {
        get => $this->backing;
    }
}
