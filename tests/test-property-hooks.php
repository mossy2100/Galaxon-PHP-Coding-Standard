<?php

declare(strict_types=1);

namespace Galaxon\CodingStandard\Tests;

use Exception;

/**
 * Test file for PHP 8.4 property hooks.
 * Used to identify and fix PHPCS sniffs that don't handle property hooks correctly.
 */
class PropertyHooksTest
{
    // region Standard properties (for comparison)

    private string $standardProperty = 'value';

    public readonly int $readonlyProperty;

    // endregion

    // region Simple property hooks

    /**
     * Property with arrow function getter.
     */
    public string $arrowGetter {
        get => $this->standardProperty;
    }

    /**
     * Property with arrow function getter and setter.
     */
    public string $arrowGetterSetter {
        get => $this->standardProperty;
        set => $this->standardProperty = $value;
    }

    /**
     * Property with block getter.
     */
    public string $blockGetter {
        get {
            return $this->standardProperty;
        }
    }

    /**
     * Property with block getter and setter.
     */
    public string $blockGetterSetter {
        get {
            return $this->standardProperty;
        }
        set {
            $this->standardProperty = $value;
        }
    }

    // endregion

    // region Property hooks with complex bodies

    /**
     * Property with multi-line block getter.
     *
     * @var array<int, string>
     */
    public array $complexGetter {
        get {
            $result = [];

            // Add some items
            $result[] = 'first';
            $result[] = 'second';

            // Process with conditional
            if ($this->standardProperty !== '') {
                $result[] = $this->standardProperty;
            }

            // Process with loop
            foreach (['a', 'b', 'c'] as $item) {
                $result[] = $item;
            }

            return $result;
        }
    }

    /**
     * Property with try-catch in getter.
     */
    public ?string $tryCatchGetter {
        get {
            try {
                return $this->standardProperty;
            } catch (Exception) {
                return null;
            }
        }
    }

    // endregion

    // region Property hooks with default values

    /**
     * Property with default value and getter.
     */
    public string $withDefault = 'default' {
        get {
            return $this->withDefault;
        }
        set {
            $this->withDefault = strtoupper($value);
        }
    }

    /**
     * Nullable property with default and lazy loading.
     *
     * @var array<int, string>|null
     */
    public ?array $lazyLoaded = null {
        get {
            if ($this->lazyLoaded === null) {
                $this->lazyLoaded = $this->loadData();
            }

            return $this->lazyLoaded;
        }
    }

    // endregion

    // region Visibility modifiers on hooks

    /**
     * Property with asymmetric visibility.
     */
    public private(set) string $asymmetricVisibility {
        get {
            return $this->asymmetricVisibility;
        }
    }

    // endregion

    // region Constructor

    public function __construct()
    {
        $this->readonlyProperty = 42;
        $this->asymmetricVisibility = 'initial';
    }

    // endregion

    // region Helper methods

    /**
     * Load data for lazy loading test.
     *
     * @return array<int, string>
     */
    private function loadData(): array
    {
        return ['loaded', 'data'];
    }

    // endregion
}
