<?php

declare(strict_types=1);

namespace ChernegaSergiy\BatteryBot\Dto;

class BatteryStatus
{
    public function __construct(
        public readonly string $percentage,
        public readonly string $status,
        public readonly float $temperature,
        public readonly string $plugged,
        public readonly string $health,
        public readonly string $current
    ) {
    }

    public function isCritical(int $threshold): bool
    {
        return is_numeric($this->percentage) && (int) $this->percentage <= $threshold;
    }

    public function getPercentageInt(): int
    {
        return is_numeric($this->percentage) ? (int) $this->percentage : 0;
    }
}
