import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

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
                <h2 className="text-lg font-bold text-gray-900">
                    Atur Ulang Kata Sandi
                </h2>
                <p className="text-xs text-gray-500 mt-1 leading-relaxed">
                    Silakan tentukan kata sandi baru yang kuat untuk mengamankan akun Academic Hub Anda.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel
                        htmlFor="email"
                        value="Alamat Email Mahasiswa"
                        className="text-xs font-semibold text-gray-700"
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
                        className="text-xs font-semibold text-gray-700"
                    />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        placeholder="Minimal 8 karakter"
                        className="mt-1 block w-full text-sm rounded-xl"
                        autoComplete="new-password"
                        isFocused={true}
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-1.5" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Konfirmasi Kata Sandi Baru"
                        className="text-xs font-semibold text-gray-700"
                    />

                    <TextInput
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        placeholder="Ulangi kata sandi baru"
                        className="mt-1 block w-full text-sm rounded-xl"
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        required
                    />

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

                <div className="pt-4 border-t border-gray-100 text-center text-xs text-gray-500">
                    Batal dan kembali ke{' '}
                    <Link
                        href={route('login')}
                        className="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                    >
                        Halaman Masuk
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

