<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_d3_ti' => 'nullable|boolean',
        ]);

        $disposableDomains = [
            'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'mailinator.com',
            'yopmail.com', 'dispostable.com', 'trashmail.com', 'getnada.com',
            'dropmail.me', 'fakemailgenerator.com', 'temp-mail.org', 'throwawaymail.com',
            'mohmal.com', 'sharklasers.com', 'guerrillamailblock.com', 'grr.la',
            'inboxkitten.com', 'crazymailing.com', 'emailondeck.com',
        ];

        $emailDomain = strtolower(substr(strrchr($request->email, "@"), 1));
        if (in_array($emailDomain, $disposableDomains)) {
            throw ValidationException::withMessages([
                'email' => 'Pendaftaran menggunakan email sementara/disposable tidak diperbolehkan. Harap gunakan akun Gmail atau email resmi Anda.',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->boolean('is_d3_ti')) {
            \App\Services\CoursePackageService::seedD3TI($user);
        }

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mengirim email verifikasi pendaftaran: ' . $e->getMessage());
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
