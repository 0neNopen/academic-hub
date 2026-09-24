<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = (string) config('services.telegram.bot_token', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Kirim pesan teks ke chat_id Telegram tertentu.
     *
     * @param string|int $chatId
     * @param string $message (Format HTML didukung)
     * @param array|null $inlineKeyboard Array tombol interaktif
     * @return bool
     */
    public function sendMessage(string|int $chatId, string $message, ?array $inlineKeyboard = null): bool
    {
        if (empty($this->botToken)) {
            Log::error('TELEGRAM_BOT_TOKEN belum diset di config/services.php atau .env');
            return false;
        }

        if (empty($chatId)) {
            Log::warning('Gagal mengirim pesan Telegram: chatId kosong');
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ];

        if (!empty($inlineKeyboard)) {
            $payload['reply_markup'] = [
                'inline_keyboard' => $inlineKeyboard,
            ];
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", $payload);

            if (!$response->successful()) {
                Log::error('Telegram API Error: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram Service Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper untuk format Inline Keyboard Button pengumpulan tugas.
     */
    public function buildSubmissionButton(?string $url): ?array
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return [
            [
                [
                    'text' => '🚀 Kumpul Tugas di Sini',
                    'url' => $url,
                ],
            ],
        ];
    }
}
