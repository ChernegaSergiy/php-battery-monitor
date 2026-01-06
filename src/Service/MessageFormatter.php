<?php

declare(strict_types=1);

namespace BatteryBot\Service;

use BatteryBot\Dto\BatteryStatus;

class MessageFormatter
{
    private const PLUGGED_MAP = [
        'AC' => 'Connected to charger',
        'USB' => 'Connected via USB',
        'Wireless' => 'Wireless charging',
        'Fast' => 'Fast charging',
        'N/A' => 'Not connected',
    ];

    private const STATUS_MAP = [
        'Charging' => 'Charging',
        'Discharging' => 'Discharging',
        'Full' => 'Full',
        'Not charging' => 'Not charging',
    ];

    private const HEALTH_MAP = [
        'Good' => 'Good condition',
        'Overheat' => 'Overheating',
        'Dead' => 'Battery dead',
        'Unspecified' => 'Unspecified',
    ];

    public function formatBatteryStatus(BatteryStatus $battery): string
    {
        return "🔋 Battery Status:\n" .
            "• Charge Level: {$battery->percentage}%\n" .
            '• Charging State: ' . $this->getPluggedStatus($battery->plugged) . "\n" .
            '• Status: ' . $this->getBatteryStatus($battery->status) . "\n" .
            '• Temperature: ' . round($battery->temperature, 1) . "°C\n" .
            '• Health: ' . $this->getHealthStatus($battery->health) . "\n" .
            "• Current: {$battery->current} µA\n";
    }

    public function formatCriticalWarning(BatteryStatus $battery): string
    {
        return "⚠️ <b>Critical Battery Warning</b> ⚠️\n" .
            "Battery level is critically low at {$battery->percentage}%.\n" .
            'Please connect the charger immediately!';
    }

    private function getPluggedStatus(string $plugged): string
    {
        return self::PLUGGED_MAP[$plugged] ?? 'Unknown';
    }

    private function getBatteryStatus(string $status): string
    {
        return self::STATUS_MAP[$status] ?? 'Unknown';
    }

    private function getHealthStatus(string $health): string
    {
        return self::HEALTH_MAP[$health] ?? 'Unknown';
    }
}
