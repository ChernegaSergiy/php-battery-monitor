<?php

declare(strict_types=1);

namespace BatteryBot;

use BatteryBot\Config\Configuration;
use BatteryBot\Logger\LoggerInterface;
use BatteryBot\Service\BatteryReader;
use BatteryBot\Service\MessageFormatter;
use BatteryBot\Service\TelegramClient;

class Application
{
    private Configuration $config;
    private LoggerInterface $logger;
    private BatteryReader $batteryReader;
    private TelegramClient $telegram;
    private MessageFormatter $formatter;

    private int $lastHourlyUpdate = -1;
    private int $lastUpdateId = 0;
    private int $lastHourlyCheckTime = 0;
    private int $lastUpdateCheckTime = 0;

    public function __construct(Configuration $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        
        $this->batteryReader = new BatteryReader();
        $this->formatter = new MessageFormatter();
        
        $this->telegram = new TelegramClient(
            $config->get('telegram.token'),
            $config->get('telegram.chat_id'),
            $logger,
            $config->get('retry.max_attempts'),
            $config->get('retry.delay')
        );
    }

    public function run(): void
    {
        $this->logger->info('Battery monitoring started');
        $this->sendInitialStatus();

        while (true) {
            try {
                $this->processUpdates();
                $this->checkHourlyUpdate();
                usleep(100000); // 100ms
            } catch (\Throwable $e) {
                $this->logger->error('Error in main loop', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                sleep(30);
            }
        }
    }

    private function sendInitialStatus(): void
    {
        try {
            $battery = $this->batteryReader->read();
            $message = $this->formatter->formatBatteryStatus($battery);
            
            $this->telegram->sendMessage($message, [
                'inline_keyboard' => [[
                    ['text' => '🔄 Refresh Data', 'callback_data' => 'refresh_battery']
                ]]
            ]);
            
            $this->handleCriticalBattery($battery);
            $this->logger->info('Initial battery status sent');
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send initial status', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function processUpdates(): void
    {
        $currentTime = time();
        
        if ($currentTime - $this->lastUpdateCheckTime < 1) {
            return;
        }

        $this->lastUpdateCheckTime = $currentTime;
        $updates = $this->telegram->getUpdates($this->lastUpdateId + 1, 1);

        if ($updates === null) {
            return;
        }

        foreach ($updates as $update) {
            $this->lastUpdateId = $update['update_id'];
            
            if (isset($update['callback_query']['data']) 
                && $update['callback_query']['data'] === 'refresh_battery'
            ) {
                $this->handleRefreshRequest($update['callback_query']['id']);
            }
        }
    }

    private function handleRefreshRequest(string $callbackId): void
    {
        try {
            $battery = $this->batteryReader->read();
            $message = $this->formatter->formatBatteryStatus($battery);
            
            $this->telegram->answerCallbackQuery($callbackId, 'Processing...');
            $this->telegram->sendMessage($message, [
                'inline_keyboard' => [[
                    ['text' => '🔄 Refresh Data', 'callback_data' => 'refresh_battery']
                ]]
            ]);
            
            $this->handleCriticalBattery($battery);
            $this->logger->info('Battery status refreshed');
        } catch (\Throwable $e) {
            $this->telegram->answerCallbackQuery($callbackId, 'Error!');
            $this->logger->error('Refresh failed', ['error' => $e->getMessage()]);
        }
    }

    private function checkHourlyUpdate(): void
    {
        $currentTime = time();
        $currentHour = (int) date('H');
        $currentMinute = (int) date('i');
        $sendMinute = $this->config->get('monitoring.send_minute');

        if ($currentMinute === $sendMinute 
            && $this->lastHourlyUpdate !== $currentHour
            && $currentTime - $this->lastHourlyCheckTime >= 55
        ) {
            $this->lastHourlyCheckTime = $currentTime;
            
            try {
                $battery = $this->batteryReader->read();
                $message = $this->formatter->formatBatteryStatus($battery);
                
                $this->telegram->sendMessage($message, [
                    'inline_keyboard' => [[
                        ['text' => '🔄 Refresh Data', 'callback_data' => 'refresh_battery']
                    ]]
                ]);
                
                $this->handleCriticalBattery($battery);
                $this->logger->info("Hourly update sent for hour {$currentHour}");
            } catch (\Throwable $e) {
                $this->logger->error('Hourly update failed', [
                    'error' => $e->getMessage()
                ]);
            }

            $this->lastHourlyUpdate = $currentHour;
        }
    }

    private function handleCriticalBattery($battery): void
    {
        $threshold = $this->config->get('monitoring.critical_threshold');
        
        if ($battery->isCritical($threshold)) {
            $message = $this->formatter->formatCriticalWarning($battery);
            $this->telegram->sendMessage($message);
            $this->logger->warning('Critical battery level', [
                'percentage' => $battery->percentage
            ]);
        }
    }
}
