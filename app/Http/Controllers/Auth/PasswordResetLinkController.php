<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [trans(Password::INVALID_USER)],
            ]);
        }

        // Buat token reset kata sandi resmi Laravel
        $token = Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        $botUsername = config('services.telegram.bot_username', 'academic_hub_notif_bot');
        $telegramSent = false;

        // 1. Coba kirimkan tautan reset langsung ke akun Telegram pengguna (jika sudah terhubung)
        if (! empty($user->telegram_chat_id)) {
            try {
                $telegram = app(\App\Services\TelegramService::class);
                $telegramMessage = "🔐 <b>Permintaan Reset Kata Sandi Academic Hub</b>\n\n"
                    . "Halo <b>" . htmlspecialchars($user->name) . "</b>,\n"
                    . "Kami menerima permintaan untuk mengatur ulang kata sandi akun Academic Hub Anda.\n\n"
                    . "Tautan ini berlaku selama 60 menit. Klik tombol di bawah ini atau buka tautan berikut untuk membuat kata sandi baru:\n\n"
                    . "🔗 <a href=\"{$resetUrl}\">{$resetUrl}</a>\n\n"
                    . "<i>Abaikan pesan ini jika Anda tidak merasa meminta pengaturan ulang kata sandi.</i>";

                $msgId = $telegram->sendMessageWithId(
                    $user->telegram_chat_id,
                    $telegramMessage,
                    [
                        [
                            ['text' => '🔑 Atur Ulang Kata Sandi', 'url' => $resetUrl],
                        ],
                    ]
                );

                if ($msgId) {
                    cache()->put('tg_reset_msg_' . $user->id, $msgId, 3600);
                    $telegramSent = true;
                }
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirimkan notifikasi reset via Telegram: ' . $e->getMessage());
            }
        }

        // Jika pengiriman via Telegram berhasil, kembalikan respons instan tanpa menunggu timeout SMTP
        if ($telegramSent) {
            if (config('mail.default') !== 'smtp') {
                try {
                    $user->sendPasswordResetNotification($token);
                } catch (\Throwable $e) {}
            }
            return back()->with('status', "✅ Tautan reset kata sandi telah BERHASIL dikirim langsung ke Telegram Anda (@{$botUsername})! Silakan periksa pesan Telegram Anda.");
        }

        // 2. Jika akun belum terhubung ke Telegram, coba kirimkan via Email
        $emailSent = false;
        try {
            $user->sendPasswordResetNotification($token);
            $emailSent = true;
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim email reset via SMTP (karena batasan port hosting Render): ' . $e->getMessage());
        }

        if ($emailSent) {
            return back()->with('status', __(Password::RESET_LINK_SENT));
        }

        // Jika email gagal terkirim dan akun belum terhubung ke Telegram
        throw ValidationException::withMessages([
            'email' => [
                "Server email (SMTP) sedang tidak dapat dijangkau di hosting Render. Buka bot Telegram @{$botUsername} dan kirim perintah /reset {$user->email} untuk menerima tautan reset kata sandi instan."
            ],
        ]);
    }
}

