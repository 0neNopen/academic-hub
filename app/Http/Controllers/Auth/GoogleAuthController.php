<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('login')->with(
                'error',
                'Autentikasi Google belum dikonfigurasi. Harap isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET pada file .env.'
            );
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (\Throwable $e) {
            Log::error('Google OAuth redirect error: ' . $e->getMessage());

            return redirect()->route('login')->with(
                'error',
                'Gagal mengalihkan ke Google: ' . $e->getMessage()
            );
        }
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());

            return redirect()->route('login')->with(
                'error',
                'Gagal menyambungkan dengan akun Google. Silakan coba kembali atau gunakan login email.'
            );
        }

        $email = $googleUser->getEmail();
        if (empty($email)) {
            return redirect()->route('login')->with(
                'error',
                'Akun Google Anda tidak menyediakan alamat email yang valid.'
            );
        }

        // Cari user yang sudah terdaftar berdasarkan google_id atau email
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->google_id = $googleUser->getId();
            if ($googleUser->getAvatar()) {
                $user->avatar = $googleUser->getAvatar();
            }
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            } else {
                $user->save();
            }
        } else {
            // Pengguna baru via Google Login
            $user = User::create([
                'name' => $googleUser->getName() ?? explode('@', $email)[0],
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
