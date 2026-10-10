import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, Eye, EyeOff, RotateCcw, Send } from 'lucide-react';
import { useState } from 'react';

export default function ResetPassword({ token, email }) {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const isTokenError = !token || (errors.email && (
        errors.email.toLowerCase().includes('token') ||
        errors.email.toLowerCase().includes('tidak valid') ||
        errors.email.toLowerCase().includes('invalid') ||
        errors.email.toLowerCase().includes('kadaluwarsa') ||
        errors.email.toLowerCase().includes('kedaluwarsa')
    ));

    const submit = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Atur Ulang Kata Sandi" />

            <div className="mb-6">
                <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                    Atur Ulang Kata Sandi
                </h2>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                    Silakan tentukan kata sandi baru yang kuat untuk mengamankan akun Academic Hub Anda.
                </p>
            </div>

            {isTokenError && (
                <div className="mb-5 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/80 space-y-3">
                    <div className="flex items-start gap-2.5">
                        <AlertTriangle className="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                        <div className="text-xs text-amber-800 dark:text-amber-200 leading-relaxed">
                            <span className="font-bold">Tautan Reset Telah Kedaluwarsa atau Tidak Valid.</span>
                            <p className="mt-1 text-amber-700 dark:text-amber-300">
                                Demi keamanan, tautan hanya berlaku 60 menit dan hanya dapat digunakan 1 kali. Silakan minta tautan baru melalui salah satu opsi berikut:
                            </p>
                        </div>
                    </div>
                    <div className="pt-2 border-t border-amber-200/60 dark:border-amber-900/60 flex flex-col sm:flex-row gap-2">
                        <Link
                            href={route('password.request')}
                            className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs transition"
                        >
                            <RotateCcw className="h-3.5 w-3.5" />
                            <span>Minta Tautan Baru di Web</span>
                        </Link>
                        <a
                            href="https://t.me/academic_hub_notif_bot"
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-white dark:bg-gray-900 border border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-xs font-semibold hover:bg-amber-100/50 transition"
                        >
                            <Send className="h-3.5 w-3.5 text-blue-500" />
                            <span>Ketik /reset di Bot Telegram</span>
                        </a>
                    </div>
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel
                        htmlFor="email"
                        value="Alamat Email Mahasiswa"
                        className="text-xs font-semibold text-gray-700 dark:text-gray-300"
                    />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        placeholder="nama@kampus.ac.id"
                        className="mt-1 block w-full text-sm rounded-xl"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-1.5" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="password"
                        value="Kata Sandi Baru"
                        className="text-xs font-semibold text-gray-700 dark:text-gray-300"
                    />

                    <div className="relative mt-1">
                        <TextInput
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            placeholder="Minimal 8 karakter"
                            className="block w-full text-sm rounded-xl pr-10"
                            autoComplete="new-password"
                            isFocused={true}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
                            title={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                        >
                            {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                    </div>

                    <InputError message={errors.password} className="mt-1.5" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Konfirmasi Kata Sandi Baru"
                        className="text-xs font-semibold text-gray-700 dark:text-gray-300"
                    />

                    <div className="relative mt-1">
                        <TextInput
                            type={showConfirmPassword ? 'text' : 'password'}
                            id="password_confirmation"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            placeholder="Ulangi kata sandi baru"
                            className="block w-full text-sm rounded-xl pr-10"
                            autoComplete="new-password"
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            required
                        />
                        <button
                            type="button"
                            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                            className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
                            title={showConfirmPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                        >
                            {showConfirmPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                    </div>

                    <InputError
                        message={errors.password_confirmation}
                        className="mt-1.5"
                    />
                </div>

                <div className="pt-2">
                    <PrimaryButton
                        className="w-full justify-center py-2.5 rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20"
                        disabled={processing}
                    >
                        {processing ? 'Menyimpan Kata Sandi...' : 'Simpan Kata Sandi Baru'}
                    </PrimaryButton>
                </div>

                <div className="pt-4 border-t border-gray-100 dark:border-gray-800 text-center text-xs text-gray-500 dark:text-gray-400">
                    Batal dan kembali ke{' '}
                    <Link
                        href={route('login')}
                        className="font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline"
                    >
                        Halaman Masuk
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

