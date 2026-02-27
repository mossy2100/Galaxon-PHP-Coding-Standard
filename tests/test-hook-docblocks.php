<?php

declare(strict_types=1);

namespace Galaxon\CodingStandard\Tests;

/**
 * Test class for docblocks in property hooks.
 */
class HookDocblocksTest
{
    private string $backing = 'value';

    /**
     * Property with docblock before hook.
     */
    public string $withDocblock {
        get => $this->backing;
    }

    /**
     * Property with block getter containing docblock.
     */
    public string $blockWithDocblock {
        /**
         * This docblock is inside the property hook container.
         */
        get {
            // A comment inside
            return $this->backing;
        }
    }

    /**
     * Property with both get and set, each with docblocks.
     */
    public string $bothHooks {
        /**
         * Getter docblock.
         */
        get {
            return $this->backing;
        }

        /**
         * Setter docblock.
         */
        set {
            $this->backing = $value;
        }
    }
}
