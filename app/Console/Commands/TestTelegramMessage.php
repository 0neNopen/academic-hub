<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TestTelegramMessage extends Command
{
    protected $signature = 'tg:test {chat_id?}';
    protected $description = 'Kirim pesan tes notifikasi bot Telegram ke chat ID tertentu';

    public function handle(TelegramService $telegramService): void
    {
        $chatId = $this->argument('chat_id') ?: config('services.telegram.test_chat_id');

        if (!$chatId) {
            $chatId = $this->ask('Masukkan Chat ID Telegram tujuan (misal: 123456789)');
        }

        if (empty($chatId)) {
            $this->error('❌ Chat ID tidak boleh kosong.');
            return;
        }

        $this->info("Mengirim pesan tes Telegram ke Chat ID: {$chatId}...");

        $msg = "🎓 <b>Academic Hub - Uji Coba Bot Telegram</b>\n\n"
             . "Halo! Integrasi bot pengingat tugas kuliah Anda di Laravel berhasil terhubung dan aktif.\n"
             . "Sistem siap memantau deadline tugas dan mengirimkan notifikasi tepat waktu! 🚀";

        $appUrl = config('app.url', 'https://academic-hub-ocw2.onrender.com');
        if (empty($appUrl) || str_contains($appUrl, 'localhost')) {
            $appUrl = 'https://academic-hub-ocw2.onrender.com';
        }

        $buttons = [
            [
                ['text' => '🌐 Kunjungi Academic Hub', 'url' => $appUrl],
            ],
        ];

        if ($telegramService->sendMessage($chatId, $msg, $buttons)) {
            $this->info('✅ Pesan Telegram berhasil terkirim!');
        } else {
            $this->error('❌ Gagal mengirim pesan. Periksa TELEGRAM_BOT_TOKEN di .env.');
        }
    }
}
