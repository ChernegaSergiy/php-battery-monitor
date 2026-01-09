<?php

declare(strict_types=1);

namespace ChernegaSergiy\BatteryBot\Logger;

use DateTime;

class FileLogger implements LoggerInterface
{
    private const LEVEL_EMERGENCY = 'EMERGENCY';
    private const LEVEL_ALERT = 'ALERT';
    private const LEVEL_CRITICAL = 'CRITICAL';
    private const LEVEL_ERROR = 'ERROR';
    private const LEVEL_WARNING = 'WARNING';
    private const LEVEL_NOTICE = 'NOTICE';
    private const LEVEL_INFO = 'INFO';
    private const LEVEL_DEBUG = 'DEBUG';

    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
        $this->ensureLogDirectory();
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_NOTICE, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        try {
            file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            error_log("Failed to write log: {$e->getMessage()}");
        }
    }

    private function ensureLogDirectory(): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Failed to create log directory: {$dir}");
        }
    }
}
