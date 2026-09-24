import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
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
                <h2 className="text-lg font-bold text-gray-900">
                    Daftar Akun Baru
                </h2>
                <p className="text-xs text-gray-500 mt-1 leading-relaxed">
                    Mulai kelola perkuliahan, jadwal harian, dan pengingat tugas kuliah Anda dalam satu wadah.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel
                        htmlFor="name"
                        value="Nama Lengkap"
                        className="text-xs font-semibold text-gray-700"
                    />

                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        placeholder="Contoh: Ahmad Fauzi"
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
                        value="Kata Sandi"
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
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-1.5" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Konfirmasi Kata Sandi"
                        className="text-xs font-semibold text-gray-700"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        placeholder="Ulangi kata sandi"
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
                        {processing ? 'Mendaftarkan Akun...' : 'Daftar Akun Sekarang'}
                    </PrimaryButton>
                </div>

                <div className="pt-4 border-t border-gray-100 text-center text-xs text-gray-500">
                    Sudah memiliki akun?{' '}
                    <Link
                        href={route('login')}
                        className="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                    >
                        Masuk ke Akun
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

