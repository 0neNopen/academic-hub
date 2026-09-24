import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Masuk" />

            <div className="mb-6">
                <h2 className="text-lg font-bold text-gray-900">
                    Masuk ke Akun
                </h2>
                <p className="text-xs text-gray-500 mt-1 leading-relaxed">
                    Gunakan email mahasiswa dan kata sandi Anda untuk mengakses jadwal & materi kuliah.
                </p>
            </div>

            {status && (
                <div className="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="email" value="Alamat Email Mahasiswa" className="text-xs font-semibold text-gray-700" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        placeholder="nama@kampus.ac.id"
                        className="mt-1 block w-full text-sm rounded-xl"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-1.5" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Kata Sandi" className="text-xs font-semibold text-gray-700" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        placeholder="••••••••"
                        className="mt-1 block w-full text-sm rounded-xl"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-1.5" />
                </div>

                <div className="flex items-center justify-between text-xs pt-1">
                    <label className="flex items-center cursor-pointer select-none">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-xs text-gray-600">
                            Ingat saya
                        </span>
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="font-medium text-blue-600 hover:text-blue-700 hover:underline"
                        >
                            Lupa kata sandi?
                        </Link>
                    )}
                </div>

                <div className="pt-2">
                    <PrimaryButton className="w-full justify-center py-2.5 rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20" disabled={processing}>
                        {processing ? 'Memproses Masuk...' : 'Masuk ke Dashboard'}
                    </PrimaryButton>
                </div>

                {/* Tautan Registrasi Akun Baru */}
                <div className="pt-4 border-t border-gray-100 text-center text-xs text-gray-500">
                    Belum memiliki akun?{' '}
                    <Link
                        href={route('register')}
                        className="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                    >
                        Daftar Akun Sekarang
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
