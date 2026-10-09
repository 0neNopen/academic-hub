<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhook extends Command
{
    protected $signature = 'tg:set-webhook {url?} {--remove : Hapus webhook dari Telegram} {--info : Tampilkan info webhook saat ini}';
    protected $description = 'Atur atau periksa webhook Telegram Bot API';

    public function handle(): void
    {
        $botToken = (string) config('services.telegram.bot_token', '');

        if (empty($botToken)) {
            $this->error('❌ TELEGRAM_BOT_TOKEN belum diatur di .env!');
            return;
        }

        if ($this->option('info')) {
            $response = Http::get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
            $this->info("Informasi Webhook Telegram:\n" . json_encode($response->json(), JSON_PRETTY_PRINT));
            return;
        }

        if ($this->option('remove')) {
            $this->info('Menghapus webhook Telegram...');
            $response = Http::post("https://api.telegram.org/bot{$botToken}/deleteWebhook");
            if ($response->json('ok')) {
                $this->info('✅ Webhook berhasil dihapus.');
            } else {
                $this->error('❌ Gagal menghapus webhook: ' . $response->body());
            }
            return;
        }

        $url = $this->argument('url');
        if (!$url) {
            $appUrl = config('app.url', 'https://academic-hub-ocw2.onrender.com');
            if (empty($appUrl) || str_contains($appUrl, 'localhost')) {
                $appUrl = 'https://academic-hub-ocw2.onrender.com';
            }
            $url = rtrim($appUrl, '/') . '/telegram/webhook';
        }

        $this->info("Mendaftarkan webhook ke Telegram API: {$url}...");

        $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
            'url' => $url,
            'drop_pending_updates' => true,
        ]);

        if ($response->json('ok')) {
            $this->info("✅ Webhook berhasil didaftarkan ke Telegram!");
            $this->line("Deskripsi: " . $response->json('description', 'Success'));
        } else {
            $this->error("❌ Gagal mendaftarkan webhook: " . $response->body());
        }
    }
}
