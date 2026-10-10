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
     * @param array|null $inlineKeyboard Array tombol interaktif inline
     * @param array|null $replyMarkup Array reply markup kustom (keyboard atau inline_keyboard)
     * @return bool
     */
    public function sendMessage(string|int $chatId, string $message, ?array $inlineKeyboard = null, ?array $replyMarkup = null): bool
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

        if (!empty($replyMarkup)) {
            $payload['reply_markup'] = $replyMarkup;
        } elseif (!empty($inlineKeyboard)) {
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
     * Kirim pesan teks dan kembalikan message_id jika berhasil.
     */
    public function sendMessageWithId(string|int $chatId, string $message, ?array $inlineKeyboard = null, ?array $replyMarkup = null): ?int
    {
        if (empty($this->botToken) || empty($chatId)) {
            return null;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ];

        if (!empty($replyMarkup)) {
            $payload['reply_markup'] = $replyMarkup;
        } elseif (!empty($inlineKeyboard)) {
            $payload['reply_markup'] = [
                'inline_keyboard' => $inlineKeyboard,
            ];
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", $payload);

            if ($response->successful()) {
                return (int) $response->json('result.message_id');
            }

            Log::error('Telegram API Error: ' . $response->body());
            return null;
        } catch (\Throwable $e) {
            Log::error('Telegram Service Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Edit teks pesan Telegram yang sudah terkirim (misal menonaktifkan tautan kedaluwarsa).
     */
    public function editMessageText(string|int $chatId, int $messageId, string $newText, ?array $inlineKeyboard = null): bool
    {
        if (empty($this->botToken) || empty($chatId) || empty($messageId)) {
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $newText,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($inlineKeyboard !== null) {
            $payload['reply_markup'] = [
                'inline_keyboard' => $inlineKeyboard,
            ];
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/editMessageText", $payload);

            if (!$response->successful()) {
                Log::warning('Telegram editMessageText response: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram Service editMessageText Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghilangkan tombol keyboard bawah agar tampilan bersih dan hanya menyisakan tombol Menu biru di kiri bawah.
     */
    public function removeKeyboardMarkup(): array
    {
        return [
            'remove_keyboard' => true,
        ];
    }

    /**
     * Mendaftarkan daftar shortcut tombol Menu biru di kiri bawah ke Telegram API.
     */
    public function setBotCommands(): bool
    {
        if (empty($this->botToken)) {
            return false;
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/setMyCommands", [
                'commands' => [
                    ['command' => 'start', 'description' => 'Mulai & cek status akun'],
                    ['command' => 'jadwal', 'description' => 'Jadwal kuliah hari ini'],
                    ['command' => 'semua_jadwal', 'description' => 'Semua jadwal kuliah (Senin-Minggu)'],
                    ['command' => 'tugas', 'description' => 'Daftar tugas & deadline aktif'],
                    ['command' => 'materi', 'description' => 'Ringkasan berkas materi kuliah'],
                    ['command' => 'reset', 'description' => 'Atur ulang kata sandi akun'],
                    ['command' => 'id', 'description' => 'Lihat Chat ID Telegram saya'],
                    ['command' => 'help', 'description' => 'Bantuan perintah bot'],
                ],
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram Service setMyCommands Exception: ' . $e->getMessage());
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
