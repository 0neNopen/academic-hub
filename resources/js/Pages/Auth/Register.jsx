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

