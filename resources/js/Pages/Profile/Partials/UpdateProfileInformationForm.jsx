import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import { Transition } from "@headlessui/react";
import { Link, useForm, usePage } from "@inertiajs/react";
import { CheckCircle2, User } from "lucide-react";

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = "",
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
        });

    const submit = (e) => {
        e.preventDefault();
        patch(route("profile.update"));
    };

    return (
        <section className={className}>
            <header>
                <div className="flex items-center gap-2">
                    <div className="p-2 rounded-lg bg-blue-600 text-white shadow-xs">
                        <User className="h-5 w-5" />
                    </div>
                    <div>
                        <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                            Informasi Profil Akun
                        </h2>
                        <p className="text-xs text-gray-600 dark:text-gray-400">
                            Perbarui nama lengkap dan alamat email utama Anda.
                        </p>
                    </div>
                </div>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-5">
                {/* Nama Lengkap */}
                <div>
                    <InputLabel htmlFor="name" value="Nama Lengkap" />
                    <TextInput
                        id="name"
                        className="mt-1 block w-full shadow-xs"
                        value={data.name}
                        placeholder="Nama lengkap Anda"
                        onChange={(e) => setData("name", e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />
                    <InputError className="mt-2" message={errors.name} />
                </div>

                {/* Alamat Email */}
                <div>
                    <InputLabel htmlFor="email" value="Alamat Email" />
                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full shadow-xs"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        required
                        autoComplete="username"
                    />
                    <InputError className="mt-2" message={errors.email} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800 dark:text-gray-200">
                            Email Anda belum terverifikasi.{" "}
                            <Link
                                href={route("verification.send")}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Klik di sini untuk mengirim ulang email verifikasi.
                            </Link>
                        </p>

                        {status === "verification-link-sent" && (
                            <div className="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                                Tautan verifikasi baru telah dikirimkan ke email Anda.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <PrimaryButton disabled={processing} className="shadow-xs">
                        {processing ? "Menyimpan..." : "Simpan Identitas Profil"}
                    </PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <CheckCircle2 className="h-4 w-4" /> Tersimpan dengan sukses.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
