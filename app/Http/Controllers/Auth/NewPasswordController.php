<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status == Password::PASSWORD_RESET) {
            // Perbarui status pesan reset di Telegram dan kirim notifikasi konfirmasi keamanan
            $user = \App\Models\User::where('email', $request->email)->first();
            if ($user && ! empty($user->telegram_chat_id)) {
                try {
                    $telegram = app(\App\Services\TelegramService::class);
                    $oldMsgId = cache()->pull('tg_reset_msg_' . $user->id);

                    // 1. Edit pesan tautan reset sebelumnya agar tombol hilang dan status diperbarui
                    if ($oldMsgId) {
                        $updatedText = "✅ <b>Kata Sandi Berhasil Diperbarui</b>\n\n"
                            . "🔒 <i>Tautan reset ini telah otomatis dinonaktifkan & kedaluwarsa demi keamanan akun Anda.</i>\n\n"
                            . "Waktu pembaruan: " . now()->setTimezone('Asia/Jakarta')->isoFormat('D MMMM YYYY, HH:mm') . " WIB";
                        $telegram->editMessageText($user->telegram_chat_id, (int) $oldMsgId, $updatedText, []);
                    }

                    // 2. Kirim pesan notifikasi keamanan baru
                    $securityAlert = "🎉 <b>Pemberitahuan Keamanan: Kata Sandi Berhasil Diperbarui!</b>\n\n"
                        . "Halo <b>" . htmlspecialchars($user->name) . "</b>,\n"
                        . "Kata sandi akun Academic Hub Anda telah berhasil diubah pada " . now()->setTimezone('Asia/Jakarta')->isoFormat('D MMMM YYYY, HH:mm:ss') . " WIB.\n\n"
                        . "Silakan masuk ke akun Anda menggunakan kata sandi yang baru.\n\n"
                        . "<i>Jika Anda tidak merasa melakukan perubahan ini, segera hubungi administrator.</i>";

                    $loginUrl = url(route('login', [], false));
                    $telegram->sendMessage($user->telegram_chat_id, $securityAlert, [
                        [
                            ['text' => '🌐 Masuk ke Akun', 'url' => $loginUrl],
                        ],
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Gagal memperbarui notifikasi Telegram setelah reset password: ' . $e->getMessage());
                }
            }

            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
