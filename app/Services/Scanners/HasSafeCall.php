<?php

namespace App\Services\Scanners;

/**
 * Shared helper for all scanner classes.
 * Provides a safe() wrapper that catches any Throwable and returns a default.
 */
trait HasSafeCall
{
    protected function safe(callable $fn, mixed $default): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return $default;
        }
    }
}
