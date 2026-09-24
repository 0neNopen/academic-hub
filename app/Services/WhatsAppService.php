<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $driver;
    protected array $config;

    public function __construct()
    {
        $this->driver = (string) config('services.whatsapp.driver', 'green_api');
        $this->config = (array) config('services.whatsapp', []);
    }

    /**
     * Memeriksa apakah driver WhatsApp telah dikonfigurasi dengan kredensial yang valid.
     */
    public function isConfigured(): bool
    {
        if ($this->driver === 'waha') {
            return !empty($this->config['waha']['url']);
        }

        return !empty($this->config['green_api']['instance_id']) && !empty($this->config['green_api']['api_token']);
    }

    /**
     * Kirim pesan WhatsApp melalui driver yang dipilih (green_api atau waha).
     */
    public function sendMessage(string $target, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::debug('WhatsAppService dilewati: Kredensial belum dikonfigurasi atau sedang ditunda.');
            return false;
        }

        if (empty($target)) {
            Log::warning('Target nomor WhatsApp kosong.');
            return false;
        }

        $formattedTarget = $this->formatPhoneNumber($target);

        return match ($this->driver) {
            'waha' => $this->sendViaWaha($formattedTarget, $message),
            default => $this->sendViaGreenApi($formattedTarget, $message),
        };
    }

    /**
     * 1. Driver WAHA (WhatsApp HTTP API - 100% Gratis & Self-Hosted via Docker/Node)
     */
    protected function sendViaWaha(string $target, string $message): bool
    {
        $url = rtrim((string) ($this->config['waha']['url'] ?? 'http://localhost:3000'), '/');
        $apiKey = (string) ($this->config['waha']['api_key'] ?? '');

        // WAHA menggunakan format JID: 6281234567890@c.us
        $chatId = str_ends_with($target, '@c.us') ? $target : "{$target}@c.us";

        try {
            $request = Http::timeout(10);
            if (!empty($apiKey)) {
                $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
            }

            $response = $request->post("{$url}/api/sendText", [
                'session' => 'default',
                'chatId' => $chatId,
                'text' => $message,
            ]);

            if (!$response->successful()) {
                Log::error('WAHA Error: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WAHA Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 2. Driver Green-API (Cloud Gratis - 100 Pesan/Hari)
     */
    protected function sendViaGreenApi(string $target, string $message): bool
    {
        $instanceId = (string) ($this->config['green_api']['instance_id'] ?? '');
        $apiToken = (string) ($this->config['green_api']['api_token'] ?? '');

        if (empty($instanceId) || empty($apiToken)) {
            Log::error('Green-API credentials belum diset di config/services.php atau .env');
            return false;
        }

        $chatId = str_ends_with($target, '@c.us') ? $target : "{$target}@c.us";
        $url = "https://api.green-api.com/waInstance{$instanceId}/sendMessage/{$apiToken}";

        try {
            $response = Http::timeout(10)->post($url, [
                'chatId' => $chatId,
                'message' => $message,
            ]);

            if (!$response->successful()) {
                Log::error('Green-API Error: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Green-API Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalisasi nomor telepon Indonesia (misal: 0812 -> 62812, +62812 -> 62812).
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }

        return $clean;
    }
}