<?php

declare(strict_types=1);

/**
 * Battery Monitoring Bot - Entry Point
 * PSR-12 compliant code with PSR-3 logging
 */

require_once __DIR__ . '/vendor/autoload.php';

use BatteryBot\Application;
use BatteryBot\Config\Configuration;
use BatteryBot\Logger\FileLogger;

try {
    $config = new Configuration([
        'telegram' => [
            'token' => 'YOUR_BOT_TOKEN',
            'chat_id' => 'YOUR_CHAT_ID',
        ],
        'monitoring' => [
            'send_minute' => 0,
            'check_interval' => 60,
            'critical_threshold' => 15,
        ],
        'logging' => [
            'path' => __DIR__ . '/logs/battery.log',
        ],
        'retry' => [
            'max_attempts' => 3,
            'delay' => 5,
        ],
    ]);

    $logger = new FileLogger($config->get('logging.path'));
    $app = new Application($config, $logger);
    
    $app->run();
} catch (Throwable $e) {
    error_log("Critical error: {$e->getMessage()}");
    exit(1);
}
