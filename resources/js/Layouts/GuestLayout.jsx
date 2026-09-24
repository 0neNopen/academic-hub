import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="min-h-screen bg-slate-50 dark:bg-gray-950 flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-8 relative overflow-hidden transition-colors duration-200">
            {/* Top theme toggle */}
            <div className="absolute top-4 right-4 z-20">
                <ThemeToggle />
            </div>

            {/* Background subtle ambient glow */}
            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-5xl h-80 bg-gradient-to-b from-blue-100/50 via-indigo-50/20 to-transparent dark:from-blue-900/20 dark:via-indigo-950/20 pointer-events-none -z-10 blur-3xl"></div>

            <div className="w-full max-w-md mx-auto">
                <div className="text-center mb-6">
                    <Link href="/" className="inline-flex flex-col items-center group">
                        <ApplicationLogo className="h-14 w-14 rounded-2xl shadow-md shadow-blue-500/10 group-hover:scale-105 transition-transform duration-200" />
                        <h1 className="mt-3 text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                            Academic Hub
                        </h1>
                        <p className="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">
                            Manajemen Jadwal Kuliah & Pengingat Tugas
                        </p>
                    </Link>
                </div>

                <div className="bg-white dark:bg-gray-900 py-7 px-5 sm:py-8 sm:px-8 shadow-xl shadow-slate-200/50 dark:shadow-black/40 rounded-2xl border border-gray-100/80 dark:border-gray-800">
                    {children}
                </div>

                <p className="mt-6 text-center text-[11px] text-gray-400 dark:text-gray-500">
                    &copy; {new Date().getFullYear()} Academic Hub • Membantu Mahasiswa Tepat Waktu
                </p>
            </div>
        </div>
    );
}

