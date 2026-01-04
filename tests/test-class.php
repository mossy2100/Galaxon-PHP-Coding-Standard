<?php

declare(strict_types=1);

class TestClass
{
    // Test private/protected properties with underscores
    private string $_privateVar;

    protected int $_protectedVar;

    public float $publicVar;

    // Test private/protected properties without underscores (correct)
    private string $privateVar;

    protected int $protectedVar;

    // Test snake_case (incorrect)
    private string $my_private_var;

    public string $my_public_var;
}
