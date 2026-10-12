import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Eye, EyeOff, GraduationCap } from 'lucide-react';
import { useState } from 'react';

export default function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        is_d3_ti: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Daftar Akun Baru" />

            <div className="mb-6">
                <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                    Daftar Akun Baru
                </h2>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                    Mulai kelola perkuliahan, jadwal harian, dan pengingat tugas kuliah Anda dalam satu wadah.
                </p>
            </div>

            {/* Tombol Daftar Cepat dengan Google */}
            <div className="mb-5">
                <a
                    href={route('auth.google.redirect')}
                    className="w-full inline-flex items-center justify-center gap-3 py-2.5 px-4 rounded-xl border border-gray-200 dark:border-gray-700/80 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs sm:text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-750 hover:border-gray-300 dark:hover:border-gray-600 shadow-2xs transition active:scale-[0.99]"
                >
                    <svg className="w-4 h-4 sm:w-5 sm:h-5 shrink-0" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" />
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
                    </svg>
                    <span>Daftar Cepat dengan Google</span>
                </a>

                <div className="relative my-4">
                    <div className="absolute inset-0 flex items-center">
                        <div className="w-full border-t border-gray-200 dark:border-gray-800" />
                    </div>
                    <div className="relative flex justify-center text-xs">
                        <span className="bg-white dark:bg-gray-900 px-3 text-gray-500 dark:text-gray-400 font-medium">
                            atau daftar dengan email manual
                        </span>
                    </div>
                </div>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel
                        htmlFor="name"
                        value="Nama Lengkap"
                        className="text-xs font-semibold text-gray-700 dark:text-gray-300"
                    />

                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        placeholder="Nama lengkap Anda"
                        className="mt-1 block w-full text-sm rounded-xl"
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />

                    <InputError message={errors.name} className="mt-1.5" />
                </div>

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
                        value="Kata Sandi"
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
                        value="Konfirmasi Kata Sandi"
                        className="text-xs font-semibold text-gray-700 dark:text-gray-300"
                    />

                    <div className="relative mt-1">
                        <TextInput
                            id="password_confirmation"
                            type={showConfirmPassword ? 'text' : 'password'}
                            name="password_confirmation"
                            value={data.password_confirmation}
                            placeholder="Ulangi kata sandi"
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

                {/* Checklist Paket D3 Teknik Informatika */}
                <div
                    onClick={() => setData('is_d3_ti', !data.is_d3_ti)}
                    className={`p-3.5 rounded-xl border transition cursor-pointer ${
                        data.is_d3_ti
                            ? 'border-blue-500 bg-blue-50/70 dark:bg-blue-950/50 dark:border-blue-700 ring-2 ring-blue-500/20'
                            : 'border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/50 hover:bg-gray-100/60 dark:hover:bg-gray-800'
                    }`}
                >
                    <div className="flex items-start gap-3 select-none">
                        <input
                            type="checkbox"
                            checked={data.is_d3_ti}
                            onChange={(e) => setData('is_d3_ti', e.target.checked)}
                            onClick={(e) => e.stopPropagation()}
                            className="mt-0.5 rounded border-gray-300 text-blue-600 shadow-xs focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600"
                        />
                        <div className="space-y-0.5 text-xs">
                            <div className="flex items-center gap-1.5 font-bold text-gray-900 dark:text-white">
                                <GraduationCap className="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                <span>Mahasiswa D3 Teknik Informatika</span>
                                <span className="text-[10px] bg-blue-600 text-white font-semibold px-2 py-0.5 rounded-full">
                                    Paket Otomatis
                                </span>
                            </div>
                            <p className="text-gray-500 dark:text-gray-400 text-[11px] leading-relaxed">
                                Otomatis isi dashboard akun Anda dengan paket lengkap 12 mata kuliah & jadwal perkuliahan Semester 3 (Kelas B).
                            </p>
                        </div>
                    </div>
                </div>

                <div className="pt-2">
                    <PrimaryButton
                        className="w-full justify-center py-2.5 rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20"
                        disabled={processing}
                    >
                        {processing ? 'Mendaftarkan Akun...' : 'Daftar Akun Sekarang'}
                    </PrimaryButton>
                </div>

                <div className="pt-4 border-t border-gray-100 dark:border-gray-800 text-center text-xs text-gray-500 dark:text-gray-400">
                    Sudah memiliki akun?{' '}
                    <Link
                        href={route('login')}
                        className="font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline"
                    >
                        Masuk ke Akun
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

