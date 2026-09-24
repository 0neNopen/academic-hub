import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    BookOpen,
    Calendar,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock,
    ExternalLink,
    Filter,
    Plus,
    Search,
    X,
} from 'lucide-react';
import { useState } from 'react';

export default function Dashboard({ auth, courses, upcomingAssignments }) {
    const [isAddCourseModalOpen, setIsAddCourseModalOpen] = useState(false);

    // Form Tambah Mata Kuliah
    const { data, setData, post, processing, reset, errors } = useForm({
        name: '',
        code: '',
        lecturer_name: '',
        day_of_week: 'Senin',
        start_time: '',
        end_time: '',
        color: '#3b82f6',
    });

    const submitCourse = (e) => {
        e.preventDefault();
        post(route('courses.store'), {
            onSuccess: () => {
                reset();
                setIsAddCourseModalOpen(false);
            },
        });
    };

    // Tandai Selesai Cepat dari Dashboard
    const [completingTaskId, setCompletingTaskId] = useState(null);

    const markAssignmentCompleted = (task) => {
        setCompletingTaskId(task.id);
        router.patch(
            route('assignments.updateStatus', task.id),
            { status: 'completed' },
            {
                preserveScroll: true,
                onFinish: () => setCompletingTaskId(null),
            }
        );
    };

    // Hari ini (Indonesia)
    const indonesianDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const todayDay = indonesianDays[new Date().getDay()];
    const todayCourses = courses.filter((c) => c.day_of_week === todayDay);
    const isTelegramActive = !!auth.user.telegram_chat_id;

    // Filter & Pencarian Mata Kuliah (Skalabilitas saat matkul banyak)
    const [courseSearch, setCourseSearch] = useState('');
    const [selectedDayFilter, setSelectedDayFilter] = useState('Semua');

    const dayFilterOptions = ['Semua', 'Hari Ini', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    const filteredCourses = courses.filter((course) => {
        const q = courseSearch.toLowerCase().trim();
        const matchesSearch =
            !q ||
            course.name.toLowerCase().includes(q) ||
            (course.code && course.code.toLowerCase().includes(q)) ||
            (course.lecturer_name && course.lecturer_name.toLowerCase().includes(q));

        const matchesDay =
            selectedDayFilter === 'Semua' ||
            (selectedDayFilter === 'Hari Ini' && course.day_of_week === todayDay) ||
            course.day_of_week === selectedDayFilter;

        return matchesSearch && matchesDay;
    });

    // Helper status urgensi deadline tugas
    const getUrgencyInfo = (deadlineString) => {
        const now = new Date();
        const deadline = new Date(deadlineString);
        const diffHours = (deadline - now) / (1000 * 60 * 60);

        if (diffHours < 0) {
            return {
                label: 'Lewat Tenggat',
                badgeClass: 'bg-rose-50 text-rose-700 border-rose-200/80',
                textClass: 'text-rose-600 font-semibold',
                isOverdue: true,
            };
        }
        if (diffHours <= 24) {
            return {
                label: '< 24 Jam (Mendesak)',
                badgeClass: 'bg-red-50 text-red-700 border-red-200 font-bold',
                textClass: 'text-red-600 font-bold',
                isUrgent: true,
            };
        }
        if (diffHours <= 72) {
            return {
                label: '< 3 Hari',
                badgeClass: 'bg-amber-50 text-amber-800 border-amber-200 font-semibold',
                textClass: 'text-amber-700 font-semibold',
                isSoon: true,
            };
        }
        return {
            label: 'Mendatang',
            badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
            textClass: 'text-gray-600',
        };
    };

    // Filter Waktu Deadline Terdekat
    const [deadlineFilter, setDeadlineFilter] = useState('all'); // 'all', 'urgent', 'week'
    const [showAllDeadlines, setShowAllDeadlines] = useState(false);

    const filteredDeadlines = upcomingAssignments.filter((task) => {
        if (deadlineFilter === 'all') return true;
        const now = new Date();
        const deadline = new Date(task.deadline);
        const diffHours = (deadline - now) / (1000 * 60 * 60);

        if (deadlineFilter === 'urgent') {
            return diffHours <= 48; // Mendesak dalam 48 jam atau lewat tenggat
        }
        if (deadlineFilter === 'week') {
            return diffHours <= 168 && diffHours >= 0; // 7 hari kedepan
        }
        return true;
    });

    const displayedDeadlines = showAllDeadlines ? filteredDeadlines : filteredDeadlines.slice(0, 5);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-bold leading-tight text-gray-900 dark:text-white">
                            Dashboard Kuliah
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Pantau perkuliahan, jadwal harian, dan tugas Anda dalam satu tempat.
                        </p>
                    </div>
                    <button
                        onClick={() => setIsAddCourseModalOpen(true)}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow hover:bg-blue-700 transition self-start sm:self-auto"
                    >
                        <Plus className="h-4 w-4" />
                        Tambah Mata Kuliah
                    </button>
                </div>
            }
        >
            <Head title="Dashboard Kuliah" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Ringkasan Cepat / Status Metrik */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-xs">
                            <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Mata Kuliah</span>
                            <div className="text-2xl font-bold text-gray-900 dark:text-white mt-1">{courses.length}</div>
                            <span className="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 block">Semester ini</span>
                        </div>

                        <div className="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-xs">
                            <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Tugas Menunggu</span>
                            <div className="text-2xl font-bold text-gray-900 dark:text-white mt-1">{upcomingAssignments.length}</div>
                            <span
                                className={`text-[11px] font-medium mt-0.5 block ${
                                    upcomingAssignments.length > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'
                                }`}
                            >
                                {upcomingAssignments.length > 0 ? 'Perlu diselesaikan' : 'Semua selesai'}
                            </span>
                        </div>

                        <div className="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-xs">
                            <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Jadwal Hari Ini</span>
                            <div className="text-2xl font-bold text-gray-900 dark:text-white mt-1">{todayCourses.length}</div>
                            <span
                                className="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 block truncate"
                                title={todayCourses.map((c) => c.name).join(', ') || 'Tidak ada kelas'}
                            >
                                {todayCourses.length > 0 ? todayCourses.map((c) => c.name).join(', ') : 'Tidak ada kelas'}
                            </span>
                        </div>

                        <div className="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-xs flex flex-col justify-between">
                            <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Notifikasi Bot</span>
                            <div className="mt-1">
                                <span
                                    className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold ${
                                        isTelegramActive
                                            ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                            : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800'
                                    }`}
                                >
                                    {isTelegramActive ? 'Telegram Aktif' : 'Belum Terhubung'}
                                </span>
                            </div>
                            <Link href={route('profile.edit')} className="text-[11px] text-blue-600 dark:text-blue-400 hover:underline mt-1 block">
                                Atur di Profil →
                            </Link>
                        </div>
                    </div>

                    {/* Konten Utama 2 Kolom (8 : 4) */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        {/* Kolom Kiri (8): Daftar Mata Kuliah + Filter & Pencarian */}
                        <div className="lg:col-span-7 xl:col-span-8 space-y-4">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div className="flex items-center gap-2">
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <BookOpen className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                        Mata Kuliah Semester Ini
                                    </h3>
                                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        ({filteredCourses.length} dari {courses.length})
                                    </span>
                                </div>

                                {/* Kotak Pencarian Instan */}
                                {courses.length > 0 && (
                                    <div className="relative w-full sm:w-60">
                                        <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400" />
                                        <input
                                            type="text"
                                            value={courseSearch}
                                            onChange={(e) => setCourseSearch(e.target.value)}
                                            placeholder="Cari matkul, kode, dosen..."
                                            className="w-full pl-8 pr-7 py-1 text-xs rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500 shadow-xs placeholder-gray-400 dark:placeholder-gray-500"
                                        />
                                        {courseSearch && (
                                            <button
                                                onClick={() => setCourseSearch('')}
                                                className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                            >
                                                <X className="h-3 w-3" />
                                            </button>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Filter Hari Kuliah (Pills Horisontal) */}
                            {courses.length > 0 && (
                                <div className="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs no-scrollbar">
                                    <span className="text-[11px] font-semibold text-gray-400 dark:text-gray-500 shrink-0 mr-1 flex items-center gap-1">
                                        <Filter className="h-3 w-3" /> Hari:
                                    </span>
                                    {dayFilterOptions.map((day) => {
                                        const isSelected = selectedDayFilter === day;
                                        const isToday = day === 'Hari Ini';
                                        return (
                                            <button
                                                key={day}
                                                onClick={() => setSelectedDayFilter(day)}
                                                className={`px-2.5 py-1 rounded-lg text-[11px] font-semibold shrink-0 transition ${
                                                    isSelected
                                                        ? 'bg-blue-600 text-white shadow-xs'
                                                        : isToday && todayCourses.length > 0
                                                        ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/60'
                                                        : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800'
                                                }`}
                                            >
                                                {day}
                                                {isToday && todayCourses.length > 0 && (
                                                    <span className="ms-1 px-1 py-0.2 bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-100 rounded-full text-[9px]">
                                                        {todayCourses.length}
                                                    </span>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            )}

                            {courses.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-8 text-center text-gray-500 shadow-xs">
                                    <BookOpen className="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500 mb-2" />
                                    <p className="font-semibold text-gray-700 dark:text-gray-200 text-sm">Belum ada mata kuliah</p>
                                    <p className="text-xs text-gray-400 dark:text-gray-500 mt-1 mb-4">Tambahkan mata kuliah yang Anda ikuti pada semester ini.</p>
                                    <button
                                        onClick={() => setIsAddCourseModalOpen(true)}
                                        className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                    >
                                        <Plus className="h-3.5 w-3.5" />
                                        Tambah Mata Kuliah Pertama
                                    </button>
                                </div>
                            ) : filteredCourses.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-8 text-center text-gray-500 shadow-xs">
                                    <Search className="mx-auto h-7 w-7 text-gray-400 dark:text-gray-500 mb-2" />
                                    <p className="font-semibold text-gray-700 dark:text-gray-200 text-sm">Tidak ada mata kuliah yang cocok</p>
                                    <p className="text-xs text-gray-400 dark:text-gray-500 mt-1 mb-3">
                                        Periksa kembali kata kunci pencarian atau filter hari yang Anda pilih.
                                    </p>
                                    <button
                                        onClick={() => {
                                            setCourseSearch('');
                                            setSelectedDayFilter('Semua');
                                        }}
                                        className="text-xs text-blue-600 dark:text-blue-400 font-semibold hover:underline"
                                    >
                                        Reset Filter Pencarian
                                    </button>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {filteredCourses.map((course) => (
                                        <Link
                                            key={course.id}
                                            href={route('courses.show', course.id)}
                                            className="group rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs hover:shadow-md hover:border-blue-200 dark:hover:border-blue-800 transition overflow-hidden flex flex-col justify-between"
                                        >
                                            <div className="p-4 sm:p-5">
                                                <div className="flex items-center justify-between mb-2.5">
                                                    <span className="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300">
                                                        {course.code || 'MK'}
                                                    </span>
                                                    <span className="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 font-medium">
                                                        <Calendar className="h-3 w-3 text-gray-400" />
                                                        {course.day_of_week || 'Jadwal fleksibel'}
                                                    </span>
                                                </div>
                                                <h4 className="font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition text-sm sm:text-base">
                                                    {course.name}
                                                </h4>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Dosen: {course.lecturer_name || '-'}
                                                </p>
                                            </div>

                                            <div className="bg-gray-50/80 dark:bg-gray-800/50 px-4 py-2.5 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                                                <span className="text-gray-600 dark:text-gray-300">
                                                    <strong className="text-gray-900 dark:text-white">{course.assignments_count}</strong> tugas aktif
                                                </span>
                                                <span className="font-semibold text-blue-600 dark:text-blue-400 group-hover:translate-x-0.5 transition-transform inline-flex items-center text-xs">
                                                    Buka Detail →
                                                </span>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Kolom Kanan (4): Deadline Tugas Terdekat (Sidebar Agenda + Aksi Selesai Cepat) */}
                        <div className="lg:col-span-5 xl:col-span-4 space-y-3">
                            <div className="flex items-center justify-between">
                                <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Clock className="h-4 w-4 text-amber-500" />
                                    Deadline Terdekat
                                </h3>
                                <span className="text-xs font-semibold px-2 py-0.5 bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 rounded-full border border-amber-200 dark:border-amber-800">
                                    {upcomingAssignments.length}
                                </span>
                            </div>

                            {/* Filter Rentang Waktu Deadline */}
                            {upcomingAssignments.length > 0 && (
                                <div className="flex items-center gap-1 bg-gray-100/80 dark:bg-gray-800/80 p-0.5 rounded-lg text-xs">
                                    {[
                                        { id: 'all', label: 'Semua' },
                                        { id: 'urgent', label: 'Mendesak' },
                                        { id: 'week', label: '7 Hari' },
                                    ].map((tab) => (
                                        <button
                                            key={tab.id}
                                            onClick={() => setDeadlineFilter(tab.id)}
                                            className={`flex-1 py-1 rounded-md text-[11px] font-semibold transition ${
                                                deadlineFilter === tab.id
                                                    ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-xs'
                                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'
                                            }`}
                                        >
                                            {tab.label}
                                        </button>
                                    ))}
                                </div>
                            )}

                            {upcomingAssignments.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 text-center text-gray-500 shadow-xs">
                                    <CheckCircle2 className="mx-auto h-7 w-7 text-emerald-500 mb-2" />
                                    <p className="font-semibold text-gray-800 dark:text-gray-200 text-xs">Semua tugas beres!</p>
                                    <p className="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                                        Tidak ada tugas aktif yang menunggu. Riwayat tugas selesai tersimpan di halaman matkul.
                                    </p>
                                </div>
                            ) : displayedDeadlines.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 text-center text-gray-500 shadow-xs">
                                    <Clock className="mx-auto h-6 w-6 text-gray-400 dark:text-gray-500 mb-1" />
                                    <p className="text-xs text-gray-600 dark:text-gray-300 font-medium">Tidak ada tugas pada filter ini.</p>
                                    <button
                                        onClick={() => setDeadlineFilter('all')}
                                        className="text-[11px] text-blue-600 dark:text-blue-400 font-semibold hover:underline mt-1"
                                    >
                                        Tampilkan Semua Deadline
                                    </button>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {displayedDeadlines.map((task) => {
                                        const urgency = getUrgencyInfo(task.deadline);
                                        const isCompleting = completingTaskId === task.id;
                                        return (
                                            <div
                                                key={task.id}
                                                className="rounded-xl bg-white dark:bg-gray-900 p-3.5 sm:p-4 border border-gray-100 dark:border-gray-800 shadow-xs hover:shadow-md transition space-y-2.5"
                                            >
                                                <div className="flex items-center justify-between text-xs gap-2">
                                                    <span className="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-[11px] truncate font-medium">
                                                        {task.course?.name}
                                                    </span>
                                                    <span className={`px-2 py-0.5 rounded text-[10px] font-semibold border ${urgency.badgeClass}`}>
                                                        {urgency.label}
                                                    </span>
                                                </div>

                                                <div>
                                                    <h4 className="font-bold text-gray-800 dark:text-gray-100 text-sm leading-snug">{task.title}</h4>
                                                    {task.description && (
                                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{task.description}</p>
                                                    )}
                                                </div>

                                                <div className="flex items-center gap-1.5 text-[11px]">
                                                    <Clock className="h-3 w-3 text-gray-400" />
                                                    <span className={urgency.textClass}>
                                                        {new Date(task.deadline).toLocaleDateString('id-ID', {
                                                            weekday: 'short',
                                                            day: 'numeric',
                                                            month: 'short',
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                        })} WIB
                                                    </span>
                                                </div>

                                                {/* Baris Tombol Aksi: Link Tugas, Detail MK, dan Tombol Tandai Selesai */}
                                                <div className="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs gap-2">
                                                    <div className="flex items-center gap-2">
                                                        {task.submission_url ? (
                                                            <a
                                                                href={task.submission_url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 font-medium hover:underline text-[11px]"
                                                            >
                                                                Link Tugas <ExternalLink className="h-2.5 w-2.5" />
                                                            </a>
                                                        ) : (
                                                            <Link
                                                                href={route('courses.show', task.course_id)}
                                                                className="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 font-medium text-[11px]"
                                                            >
                                                                Detail MK →
                                                            </Link>
                                                        )}
                                                    </div>

                                                    {/* Tombol Cepat Tandai Selesai Langsung dari Dashboard */}
                                                    <button
                                                        type="button"
                                                        disabled={isCompleting}
                                                        onClick={() => markAssignmentCompleted(task)}
                                                        className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 transition disabled:opacity-50"
                                                        title="Tandai tugas ini sudah selesai"
                                                    >
                                                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                                        {isCompleting ? 'Menyimpan...' : 'Tandai Selesai'}
                                                    </button>
                                                </div>
                                            </div>
                                        );
                                    })}

                                    {/* Tombol Tampilkan Lebih Banyak jika melebihi 5 */}
                                    {filteredDeadlines.length > 5 && (
                                        <button
                                            type="button"
                                            onClick={() => setShowAllDeadlines(!showAllDeadlines)}
                                            className="w-full py-2 text-center text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition flex items-center justify-center gap-1"
                                        >
                                            {showAllDeadlines ? (
                                                <>
                                                    <ChevronUp className="h-3.5 w-3.5" />
                                                    Tampilkan 5 Teratas
                                                </>
                                            ) : (
                                                <>
                                                    <ChevronDown className="h-3.5 w-3.5" />
                                                    Lihat Semua ({filteredDeadlines.length} Tugas)
                                                </>
                                            )}
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal Sederhana Tambah Mata Kuliah */}
            {isAddCourseModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 dark:bg-black/75 backdrop-blur-xs p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Tambah Mata Kuliah</h3>
                        <form onSubmit={submitCourse} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Nama Mata Kuliah *</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="Contoh: Pemrograman Web Lanjut"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                                />
                                {errors.name && <p className="text-xs text-red-500 mt-1">{errors.name}</p>}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Kode MK</label>
                                    <input
                                        type="text"
                                        placeholder="Contoh: IF-302"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Hari Kuliah</label>
                                    <select
                                        value={data.day_of_week}
                                        onChange={(e) => setData('day_of_week', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        {['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'].map((d) => (
                                             <option key={d} value={d}>{d}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Nama Dosen Pengampu</label>
                                <input
                                    type="text"
                                    placeholder="Contoh: Dr. Budi Santoso, M.Kom"
                                    value={data.lecturer_name}
                                    onChange={(e) => setData('lecturer_name', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    onClick={() => setIsAddCourseModalOpen(false)}
                                    className="rounded-lg px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition"
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan Mata Kuliah'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}