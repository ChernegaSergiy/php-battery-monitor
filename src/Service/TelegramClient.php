<?php

declare(strict_types=1);

namespace ChernegaSergiy\BatteryBot\Service;

use ChernegaSergiy\BatteryBot\Logger\LoggerInterface;

class TelegramClient
{
    private string $apiToken;
    private string $chatId;
    private LoggerInterface $logger;
    private int $maxRetries;
    private int $retryDelay;

    public function __construct(
        string $apiToken,
        string $chatId,
        LoggerInterface $logger,
        int $maxRetries = 3,
        int $retryDelay = 5
    ) {
        $this->apiToken = $apiToken;
        $this->chatId = $chatId;
        $this->logger = $logger;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }

    public function sendMessage(string $text, array $replyMarkup = null): bool
    {
        $data = [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $data['reply_markup'] = $replyMarkup;
        }

        return $this->sendRequest('sendMessage', $data);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text): bool
    {
        return $this->sendRequest('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => false,
        ]);
    }

    public function getUpdates(int $offset, int $timeout = 1): ?array
    {
        $data = [
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['callback_query'],
        ];

        $response = $this->sendRequestSync('getUpdates', $data, $timeout + 2);
        
        if ($response === null) {
            return null;
        }

        return $response['result'] ?? null;
    }

    private function sendRequest(string $method, array $data): bool
    {
        $url = $this->getApiUrl($method);
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $result = $this->executeAsyncRequest($url, $data);
                if ($result) {
                    return true;
                }
                
                if ($attempt < $this->maxRetries) {
                    $this->logger->warning("Retry attempt {$attempt}/{$this->maxRetries}");
                    sleep($this->retryDelay);
                }
            } catch (\Throwable $e) {
                $this->logger->error("Request failed: {$e->getMessage()}");
                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay);
                }
            }
        }

        return false;
    }

    private function sendRequestSync(string $method, array $data, int $timeout): ?array
    {
        $url = $this->getApiUrl($method);
        $ch = curl_init($url);

        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_NOSIGNAL => 1,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            return null;
        }

        $decoded = json_decode($response, true);
        return ($decoded['ok'] ?? false) ? $decoded : null;
    }

    private function executeAsyncRequest(string $url, array $data): bool
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_NOSIGNAL => 1,
        ]);

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);

        $active = null;
        $started = time();
        $timeout = 5;

        do {
            $status = curl_multi_exec($mh, $active);
            
            if (time() - $started > $timeout) {
                break;
            }

            if ($active) {
                curl_multi_select($mh, 0.1);
            }

            usleep(5000);
        } while ($active && $status === CURLM_OK);

        $response = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($response)) {
            $decoded = json_decode($response, true);
            return ($decoded['ok'] ?? false) === true;
        }

        return false;
    }

    private function getApiUrl(string $method): string
    {
        return "https://api.telegram.org/bot{$this->apiToken}/{$method}";
    }
}
