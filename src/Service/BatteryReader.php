<?php

declare(strict_types=1);

namespace ChernegaSergiy\BatteryBot\Service;

use ChernegaSergiy\BatteryBot\Dto\BatteryStatus;
use ChernegaSergiy\BatteryBot\Exception\BatteryReadException;

class BatteryReader
{
    private const BATTERY_PATH = '/sys/class/power_supply/battery/';
    
    private const PARAMS = [
        'percentage' => 'capacity',
        'status' => 'status',
        'temperature' => 'temp',
        'plugged' => 'charge_type',
        'health' => 'health',
        'current' => 'current_now',
    ];

    public function read(): BatteryStatus
    {
        $data = [];

        foreach (self::PARAMS as $key => $param) {
            try {
                $data[$key] = $this->readParameter($param);
            } catch (BatteryReadException $e) {
                $data[$key] = $this->getDefaultValue($key);
            }
        }

        if (is_numeric($data['temperature'])) {
            $data['temperature'] = (float) $data['temperature'] / 10;
        }

        return new BatteryStatus(
            $data['percentage'],
            $data['status'],
            (float) $data['temperature'],
            $data['plugged'],
            $data['health'],
            $data['current']
        );
    }

    private function readParameter(string $param): string
    {
        $path = self::BATTERY_PATH . $param;

        if (!file_exists($path) || !is_readable($path)) {
            throw new BatteryReadException("Unable to read {$param} from {$path}");
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            throw new BatteryReadException("Failed to read content from {$path}");
        }

        return trim($content);
    }

    private function getDefaultValue(string $key): string
    {
        return match ($key) {
            'percentage' => 'Unknown',
            'temperature' => '0',
            default => 'N/A',
        };
    }
}
