<?php

declare(strict_types=1);

/**
 * Battery Monitoring Bot - Entry Point
 * PSR-12 compliant code with PSR-3 logging
 */

namespace BatteryBot\Config;

class Configuration
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function all(): array
    {
        return $this->config;
    }
}
