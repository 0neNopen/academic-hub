import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    ArrowUpDown,
    BookOpen,
    Calendar,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock,
    Download,
    ExternalLink,
    Eye,
    FileText,
    Filter,
    Image as ImageIcon,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';

export default function CourseShow({ course }) {
    const [isMaterialModalOpen, setIsMaterialModalOpen] = useState(false);
    const [isEditMaterialModalOpen, setIsEditMaterialModalOpen] = useState(false);
    const [editingMaterial, setEditingMaterial] = useState(null);
    const [isAssignmentModalOpen, setIsAssignmentModalOpen] = useState(false);
    const [isEditCourseModalOpen, setIsEditCourseModalOpen] = useState(false);
    const [editingAssignment, setEditingAssignment] = useState(null);
    const [previewMaterial, setPreviewMaterial] = useState(null);
    const [isHistoryOpen, setIsHistoryOpen] = useState(false);
    const [processingTaskId, setProcessingTaskId] = useState(null);

    // Pencarian & Filter Materi Kuliah
    const [materialSearch, setMaterialSearch] = useState('');
    const [selectedTypeFilter, setSelectedTypeFilter] = useState('all');
    const [selectedMeetingFilter, setSelectedMeetingFilter] = useState('all');
    const [materialSort, setMaterialSort] = useState('meeting_asc');

    const availableMeetings = Array.from(
        new Set(course.materials.map((m) => m.meeting_number).filter(Boolean))
    ).sort((a, b) => a - b);

    // Pemisahan tugas aktif & riwayat tugas selesai
    const activeAssignments = course.assignments.filter((t) => t.status !== 'completed');
    const completedAssignments = course.assignments.filter((t) => t.status === 'completed');

    // Helper format datetime-local (YYYY-MM-DDTHH:mm)
    const formatForDateTimeLocal = (dateString) => {
        if (!dateString) return '';
        const d = new Date(dateString);
        if (isNaN(d.getTime())) return '';
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    };

    // Helper format tanggal & waktu Indonesia
    const formatTimestamp = (dateString) => {
        if (!dateString) return '-';
        const d = new Date(dateString);
        if (isNaN(d.getTime())) return '-';
        return d.toLocaleDateString('id-ID', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }) + ' WIB';
    };

    // Helper identifikasi tipe file berkas materi
    const getFileInfo = (filePath) => {
        if (!filePath) {
            return {
                ext: '',
                type: 'none',
                canPreviewInline: false,
                badgeClass: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
                iconBg: 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                label: 'Tautan / Referensi',
            };
        }
        const ext = filePath.split('.').pop().toLowerCase();

        if (ext === 'pdf') {
            return {
                ext: 'PDF',
                type: 'pdf',
                canPreviewInline: true,
                badgeClass: 'bg-red-50 text-red-700 border border-red-200/60 dark:bg-red-950/60 dark:text-red-300 dark:border-red-900/60',
                iconBg: 'bg-red-50 text-red-600 dark:bg-red-950/60 dark:text-red-400',
                label: 'Dokumen PDF',
            };
        }

        if (['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'].includes(ext)) {
            return {
                ext: ext.toUpperCase(),
                type: 'image',
                canPreviewInline: true,
                badgeClass: 'bg-emerald-50 text-emerald-700 border border-emerald-200/60 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900/60',
                iconBg: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
                label: 'Berkas Gambar',
            };
        }

        if (['doc', 'docx'].includes(ext)) {
            return {
                ext: ext.toUpperCase(),
                type: 'word',
                canPreviewInline: false,
                badgeClass: 'bg-blue-50 text-blue-700 border border-blue-200/60 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-900/60',
                iconBg: 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
                label: 'Microsoft Word',
            };
        }

        if (['ppt', 'pptx'].includes(ext)) {
            return {
                ext: ext.toUpperCase(),
                type: 'presentation',
                canPreviewInline: false,
                badgeClass: 'bg-amber-50 text-amber-700 border border-amber-200/60 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-900/60',
                iconBg: 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
                label: 'Slide Presentasi',
            };
        }

        if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
            return {
                ext: ext.toUpperCase(),
                type: 'archive',
                canPreviewInline: false,
                badgeClass: 'bg-purple-50 text-purple-700 border border-purple-200/60 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-900/60',
                iconBg: 'bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400',
                label: 'Berkas Arsip',
            };
        }

        return {
            ext: ext.toUpperCase(),
            type: 'other',
            canPreviewInline: false,
            badgeClass: 'bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            iconBg: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
            label: 'Berkas Dokumen',
        };
    };

    // Form Tambah Materi (Default Pertemuan 1)
    const materialForm = useForm({
        title: '',
        meeting_number: 1,
        external_link: '',
        notes: '',
        file: null,
    });

    const submitMaterial = (e) => {
        e.preventDefault();
        materialForm.post(route('materials.store', course.id), {
            onSuccess: () => {
                materialForm.reset();
                materialForm.setData('meeting_number', 1);
                setIsMaterialModalOpen(false);
            },
        });
    };

    // Form Edit Materi
    const editMaterialForm = useForm({
        _method: 'put',
        title: '',
        meeting_number: 1,
        external_link: '',
        notes: '',
        file: null,
    });

    const openEditMaterial = (item) => {
        setEditingMaterial(item);
        editMaterialForm.setData({
            _method: 'put',
            title: item.title || '',
            meeting_number: item.meeting_number ?? 1,
            external_link: item.external_link || '',
            notes: item.notes || '',
            file: null,
        });
        setIsEditMaterialModalOpen(true);
    };

    const submitEditMaterial = (e) => {
        e.preventDefault();
        if (!editingMaterial) return;

        editMaterialForm.post(route('materials.update', editingMaterial.id), {
            onSuccess: () => {
                setIsEditMaterialModalOpen(false);
                setEditingMaterial(null);
                editMaterialForm.reset();
            },
        });
    };

    // Filter Tabs untuk Format Berkas
    const formatFilterTabs = [
        { key: 'all', label: 'Semua', count: course.materials.length },
        {
            key: 'pdf',
            label: 'PDF',
            count: course.materials.filter((m) => getFileInfo(m.file_path).type === 'pdf').length,
        },
        {
            key: 'image',
            label: 'Foto / Gambar',
            count: course.materials.filter((m) => getFileInfo(m.file_path).type === 'image').length,
        },
        {
            key: 'word',
            label: 'Word',
            count: course.materials.filter((m) => getFileInfo(m.file_path).type === 'word').length,
        },
        {
            key: 'presentation',
            label: 'Slide PPT',
            count: course.materials.filter((m) => getFileInfo(m.file_path).type === 'presentation').length,
        },
        {
            key: 'archive',
            label: 'Arsip ZIP',
            count: course.materials.filter((m) => getFileInfo(m.file_path).type === 'archive').length,
        },
        {
            key: 'link',
            label: 'Tautan Web',
            count: course.materials.filter((m) => !m.file_path && !!m.external_link).length,
        },
    ];

    const filteredMaterials = course.materials
        .filter((item) => {
            const info = getFileInfo(item.file_path);
            const q = materialSearch.toLowerCase().trim();

            const matchesSearch =
                !q ||
                item.title.toLowerCase().includes(q) ||
                (item.notes && item.notes.toLowerCase().includes(q)) ||
                (item.meeting_number && `pertemuan ${item.meeting_number}`.includes(q)) ||
                (info.ext && info.ext.toLowerCase().includes(q));

            let matchesType = true;
            if (selectedTypeFilter === 'pdf') {
                matchesType = info.type === 'pdf';
            } else if (selectedTypeFilter === 'image') {
                matchesType = info.type === 'image';
            } else if (selectedTypeFilter === 'word') {
                matchesType = info.type === 'word';
            } else if (selectedTypeFilter === 'presentation') {
                matchesType = info.type === 'presentation';
            } else if (selectedTypeFilter === 'archive') {
                matchesType = info.type === 'archive';
            } else if (selectedTypeFilter === 'link') {
                matchesType = !item.file_path && !!item.external_link;
            }

            let matchesMeeting = true;
            if (selectedMeetingFilter !== 'all') {
                matchesMeeting = String(item.meeting_number) === String(selectedMeetingFilter);
            }

            return matchesSearch && matchesType && matchesMeeting;
        })
        .sort((a, b) => {
            if (materialSort === 'meeting_asc') {
                return (a.meeting_number || 999) - (b.meeting_number || 999);
            }
            if (materialSort === 'meeting_desc') {
                return (b.meeting_number || 0) - (a.meeting_number || 0);
            }
            if (materialSort === 'latest_added') {
                return new Date(b.created_at) - new Date(a.created_at);
            }
            return 0;
        });

    // Form Tambah Tugas
    const assignmentForm = useForm({
        title: '',
        description: '',
        deadline: '',
        submission_url: '',
    });

    const submitAssignment = (e) => {
        e.preventDefault();
        assignmentForm.post(route('assignments.store', course.id), {
            onSuccess: () => {
                assignmentForm.reset();
                setIsAssignmentModalOpen(false);
            },
        });
    };

    // Form Edit Mata Kuliah
    const editCourseForm = useForm({
        name: course.name || '',
        code: course.code || '',
        lecturer_name: course.lecturer_name || '',
        day_of_week: course.day_of_week || 'Senin',
        start_time: course.start_time || '',
        end_time: course.end_time || '',
        color: course.color || '#3b82f6',
    });

    const openEditCourse = () => {
        editCourseForm.setData({
            name: course.name || '',
            code: course.code || '',
            lecturer_name: course.lecturer_name || '',
            day_of_week: course.day_of_week || 'Senin',
            start_time: course.start_time || '',
            end_time: course.end_time || '',
            color: course.color || '#3b82f6',
        });
        setIsEditCourseModalOpen(true);
    };

    const submitEditCourse = (e) => {
        e.preventDefault();
        editCourseForm.put(route('courses.update', course.id), {
            onSuccess: () => {
                setIsEditCourseModalOpen(false);
            },
        });
    };

    // Form Edit Tugas Kuliah
    const editAssignmentForm = useForm({
        title: '',
        description: '',
        deadline: '',
        submission_url: '',
    });

    const openEditAssignment = (task) => {
        setEditingAssignment(task);
        editAssignmentForm.setData({
            title: task.title || '',
            description: task.description || '',
            deadline: formatForDateTimeLocal(task.deadline),
            submission_url: task.submission_url || '',
        });
    };

    const submitEditAssignment = (e) => {
        e.preventDefault();
        if (!editingAssignment) return;
        editAssignmentForm.put(route('assignments.update', editingAssignment.id), {
            onSuccess: () => {
                setEditingAssignment(null);
            },
        });
    };

    const toggleAssignmentStatus = (task) => {
        const nextStatus = task.status === 'completed' ? 'pending' : 'completed';
        setProcessingTaskId(task.id);
        router.patch(
            route('assignments.updateStatus', task.id),
            { status: nextStatus },
            {
                preserveScroll: true,
                onFinish: () => setProcessingTaskId(null),
            }
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('dashboard')}
                            className="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-xs font-mono font-bold text-blue-600 dark:text-blue-400 uppercase bg-blue-50 dark:bg-blue-950/70 border border-blue-200/60 dark:border-blue-900/60 px-2 py-0.5 rounded">
                                    {course.code || 'MK'}
                                </span>
                                <h2 className="text-xl font-bold text-gray-800 dark:text-white leading-tight">
                                    {course.name}
                                </h2>
                            </div>
                            <div className="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">
                                <span className="flex items-center gap-1">
                                    <Calendar className="h-3.5 w-3.5 text-blue-500" />
                                    {course.day_of_week || 'Jadwal fleksibel'}
                                </span>
                                <span>•</span>
                                <span>Dosen: {course.lecturer_name || '-'}</span>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <button
                            onClick={openEditCourse}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-750 transition"
                        >
                            <Pencil className="h-3.5 w-3.5 text-gray-500 dark:text-gray-400" />
                            Edit Matkul
                        </button>
                        <button
                            onClick={() => setIsMaterialModalOpen(true)}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-750 transition"
                        >
                            <Plus className="h-4 w-4" />
                            Materi
                        </button>
                        <button
                            onClick={() => setIsAssignmentModalOpen(true)}
                            className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-blue-700 transition"
                        >
                            <Plus className="h-4 w-4" />
                            Tugas Baru
                        </button>
                    </div>
                </div>
            }
        >
            <Head title={`Detail: ${course.name}`} />

            <div className="py-8">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        {/* Kolom Kiri: Daftar Materi */}
                        <div className="lg:col-span-7 xl:col-span-8 space-y-4">
                            {/* Header Judul & Search Bar */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div className="flex items-center gap-2">
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <BookOpen className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        Arsip Materi & Slide Kuliah
                                    </h3>
                                    {course.materials.length > 0 && (
                                        <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border dark:border-gray-700">
                                            {filteredMaterials.length === course.materials.length
                                                ? `${course.materials.length} materi`
                                                : `${filteredMaterials.length} dari ${course.materials.length}`}
                                        </span>
                                    )}
                                </div>

                                {/* Search Box */}
                                {course.materials.length > 0 && (
                                    <div className="relative w-full sm:w-64">
                                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 dark:text-gray-500" />
                                        <input
                                            type="text"
                                            value={materialSearch}
                                            onChange={(e) => setMaterialSearch(e.target.value)}
                                            placeholder="Cari materi, judul, P1, format..."
                                            className="w-full pl-8 pr-8 py-1.5 text-xs rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500 shadow-2xs"
                                        />
                                        {materialSearch && (
                                            <button
                                                type="button"
                                                onClick={() => setMaterialSearch('')}
                                                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                            >
                                                <X className="h-3.5 w-3.5" />
                                            </button>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Filter Format Berkas & Opsi Pertemuan / Urutan */}
                            {course.materials.length > 0 && (
                                <div className="space-y-2.5 bg-gray-50/70 dark:bg-gray-900/60 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                                    {/* Baris 1: Filter Tipe Format Berkas (Pills) */}
                                    <div className="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs no-scrollbar">
                                        <span className="text-[11px] font-semibold text-gray-400 dark:text-gray-500 shrink-0 mr-1 flex items-center gap-1">
                                            <Filter className="h-3 w-3" /> Format:
                                        </span>
                                        {formatFilterTabs.map((tab) => {
                                            const isSelected = selectedTypeFilter === tab.key;
                                            if (tab.count === 0 && tab.key !== 'all') return null;
                                            return (
                                                <button
                                                    key={tab.key}
                                                    type="button"
                                                    onClick={() => setSelectedTypeFilter(tab.key)}
                                                    className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold shrink-0 transition ${
                                                        isSelected
                                                            ? 'bg-blue-600 text-white shadow-xs'
                                                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750'
                                                    }`}
                                                >
                                                    <span>{tab.label}</span>
                                                    <span
                                                        className={`text-[9px] px-1 py-0.2 rounded-full ${
                                                            isSelected
                                                                ? 'bg-blue-500 text-white'
                                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                                                        }`}
                                                    >
                                                        {tab.count}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>

                                    {/* Baris 2: Filter Pertemuan & Pengurutan */}
                                    <div className="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-gray-200/50 dark:border-gray-800 text-xs">
                                        <div className="flex items-center gap-2">
                                            {availableMeetings.length > 1 && (
                                                <div className="flex items-center gap-1.5">
                                                    <span className="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Pertemuan:</span>
                                                    <select
                                                        value={selectedMeetingFilter}
                                                        onChange={(e) => setSelectedMeetingFilter(e.target.value)}
                                                        className="py-1 pl-2.5 pr-8 text-xs rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 shadow-xs font-medium cursor-pointer"
                                                    >
                                                        <option value="all">Semua Pertemuan</option>
                                                        {availableMeetings.map((p) => (
                                                            <option key={p} value={p}>
                                                                Pertemuan {p}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                            )}
                                        </div>

                                        <div className="flex items-center gap-1.5 ml-auto">
                                            <ArrowUpDown className="h-3 w-3 text-gray-400 dark:text-gray-500" />
                                            <span className="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Urutkan:</span>
                                            <select
                                                value={materialSort}
                                                onChange={(e) => setMaterialSort(e.target.value)}
                                                className="py-1 pl-2.5 pr-8 text-xs rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 shadow-xs font-medium cursor-pointer"
                                            >
                                                <option value="meeting_asc">Pertemuan (1 → N)</option>
                                                <option value="meeting_desc">Pertemuan (N → 1)</option>
                                                <option value="latest_added">Terbaru Diunggah</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {course.materials.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 text-center text-gray-500 dark:text-gray-400">
                                    <FileText className="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600 mb-2" />
                                    <p className="text-sm">Belum ada berkas atau tautan materi yang diunggah.</p>
                                </div>
                            ) : filteredMaterials.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-8 text-center text-gray-500 dark:text-gray-400 shadow-2xs">
                                    <Search className="mx-auto h-7 w-7 text-gray-400 dark:text-gray-500 mb-2" />
                                    <p className="font-semibold text-gray-700 dark:text-gray-200 text-sm">Tidak ada materi yang cocok</p>
                                    <p className="text-xs text-gray-400 dark:text-gray-500 mt-1 mb-3">
                                        Coba sesuaikan kata kunci pencarian, filter format, atau filter pertemuan Anda.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setMaterialSearch('');
                                            setSelectedTypeFilter('all');
                                            setSelectedMeetingFilter('all');
                                        }}
                                        className="text-xs text-blue-600 dark:text-blue-400 font-semibold hover:underline"
                                    >
                                        Reset Filter Pencarian
                                    </button>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {filteredMaterials.map((item) => {
                                        const itemFileInfo = getFileInfo(item.file_path);
                                        return (
                                            <div
                                                key={item.id}
                                                className="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm flex items-start justify-between gap-4"
                                            >
                                                <div className="flex items-start gap-3">
                                                    <div className={`p-2 rounded-lg mt-0.5 ${itemFileInfo.iconBg}`}>
                                                        {itemFileInfo.type === 'image' ? (
                                                            <ImageIcon className="h-5 w-5" />
                                                        ) : (
                                                            <FileText className="h-5 w-5" />
                                                        )}
                                                    </div>
                                                    <div className="space-y-1">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            {item.meeting_number && (
                                                                <span className="text-xs font-semibold px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700 rounded">
                                                                    Pertemuan {item.meeting_number}
                                                                </span>
                                                            )}
                                                            <h4 className="font-semibold text-gray-800 dark:text-gray-100 text-sm">
                                                                {item.title}
                                                            </h4>
                                                            {item.file_path && (
                                                                <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider ${itemFileInfo.badgeClass}`}>
                                                                    {itemFileInfo.ext}
                                                                </span>
                                                            )}
                                                        </div>
                                                        {item.notes && (
                                                            <p className="text-xs text-gray-500 dark:text-gray-400">{item.notes}</p>
                                                        )}

                                                        {/* Waktu Input Materi */}
                                                        <div className="flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-gray-500 pt-0.5">
                                                            <Clock className="h-3 w-3 text-gray-400 dark:text-gray-500" />
                                                            <span>Diinput: {formatTimestamp(item.created_at)}</span>
                                                        </div>

                                                        <div className="flex items-center gap-3 pt-2 text-xs">
                                                            {item.file_path && (
                                                                <>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => setPreviewMaterial(item)}
                                                                        className="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline font-semibold"
                                                                    >
                                                                        <Eye className="h-3.5 w-3.5" /> {itemFileInfo.type === 'image' ? 'Lihat Foto' : 'Lihat Dokumen'}
                                                                    </button>
                                                                    <a
                                                                        href={route('materials.download', item.id)}
                                                                        className="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:underline font-medium"
                                                                    >
                                                                        <Download className="h-3.5 w-3.5" /> Unduh
                                                                    </a>
                                                                </>
                                                            )}
                                                            {item.external_link && (
                                                                <a
                                                                    href={item.external_link}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="inline-flex items-center gap-1 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:underline font-medium"
                                                                >
                                                                    <ExternalLink className="h-3.5 w-3.5" /> Link Referensi
                                                                </a>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="flex items-center gap-1 shrink-0">
                                                    <button
                                                        type="button"
                                                        onClick={() => openEditMaterial(item)}
                                                        title="Edit Materi"
                                                        className="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                                                    >
                                                        <Pencil className="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            if (confirm('Hapus materi ini?')) {
                                                                router.delete(route('materials.destroy', item.id), {
                                                                    preserveScroll: true,
                                                                });
                                                            }
                                                        }}
                                                        title="Hapus Materi"
                                                        className="text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {/* Kolom Kanan: Daftar Tugas & Riwayat Selesai */}
                        <div className="lg:col-span-5 xl:col-span-4 space-y-5">
                            {/* 1. Tugas Aktif yang Perlu Dikerjakan */}
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <Clock className="h-4 w-4 text-amber-500" />
                                        Tugas Aktif
                                    </h3>
                                    <span className="text-xs font-semibold px-2 py-0.5 bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 rounded-full border dark:border-amber-900/60">
                                        {activeAssignments.length}
                                    </span>
                                </div>

                                {activeAssignments.length === 0 ? (
                                    <div className="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 text-center text-gray-500 dark:text-gray-400 shadow-xs">
                                        <CheckCircle2 className="mx-auto h-7 w-7 text-emerald-500 mb-1.5" />
                                        <p className="text-xs font-bold text-gray-800 dark:text-gray-200">Semua tugas beres!</p>
                                        <p className="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                            {completedAssignments.length > 0
                                                ? 'Semua tugas telah diselesaikan dan tersimpan di riwayat bawah.'
                                                : 'Belum ada tugas yang ditambahkan untuk mata kuliah ini.'}
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {activeAssignments.map((task) => (
                                            <div
                                                key={task.id}
                                                className="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm space-y-2.5 hover:shadow-md transition"
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <h4 className="font-bold text-sm text-gray-800 dark:text-gray-100 leading-snug">
                                                        {task.title}
                                                    </h4>
                                                    <div className="flex items-center gap-1 shrink-0">
                                                        <button
                                                            onClick={() => openEditAssignment(task)}
                                                            title="Edit Tugas"
                                                            className="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition p-1"
                                                        >
                                                            <Pencil className="h-3.5 w-3.5" />
                                                        </button>
                                                        <button
                                                            onClick={() => {
                                                                if (confirm('Hapus tugas ini?')) {
                                                                    router.delete(route('assignments.destroy', task.id), {
                                                                        preserveScroll: true,
                                                                    });
                                                                }
                                                            }}
                                                            title="Hapus Tugas"
                                                            className="text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition p-1"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </button>
                                                    </div>
                                                </div>

                                                {task.description && (
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{task.description}</p>
                                                )}

                                                <div className="flex items-center gap-1.5 text-xs font-semibold text-red-600 dark:text-red-400">
                                                    <Clock className="h-3.5 w-3.5" />
                                                    <span>
                                                        {new Date(task.deadline).toLocaleDateString('id-ID', {
                                                            weekday: 'short',
                                                            day: 'numeric',
                                                            month: 'short',
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                        })} WIB
                                                    </span>
                                                </div>

                                                {/* Baris Aksi: Link Pengumpulan & Tombol Tandai Selesai */}
                                                <div className="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs gap-2">
                                                    {task.submission_url ? (
                                                        <a
                                                            href={task.submission_url}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline"
                                                        >
                                                            Link Tugas <ExternalLink className="h-3 w-3" />
                                                        </a>
                                                    ) : (
                                                        <span className="text-[11px] text-gray-400 dark:text-gray-500">Belum ada link</span>
                                                    )}

                                                    {/* Tombol Tandai Selesai */}
                                                    <button
                                                        type="button"
                                                        disabled={processingTaskId === task.id}
                                                        onClick={() => toggleAssignmentStatus(task)}
                                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/70 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 transition disabled:opacity-50"
                                                        title="Pindahkan tugas ini ke riwayat selesai"
                                                    >
                                                        <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                                        <span>{processingTaskId === task.id ? 'Menyimpan...' : 'Tandai Selesai'}</span>
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* 2. Riwayat Tugas Selesai (Disimpan Rapi di Bawah, Bisa Buka-Tutup) */}
                            {completedAssignments.length > 0 && (
                                <div className="pt-3 border-t border-gray-200 dark:border-gray-800 space-y-3">
                                    <button
                                        type="button"
                                        onClick={() => setIsHistoryOpen(!isHistoryOpen)}
                                        className="w-full flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-900/80 hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200/80 dark:border-gray-800 transition text-left"
                                    >
                                        <div className="flex items-center gap-2 text-xs font-bold text-gray-800 dark:text-gray-200">
                                            <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                            <span>Riwayat Tugas Selesai</span>
                                            <span className="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 rounded-full border dark:border-emerald-900/60">
                                                {completedAssignments.length}
                                            </span>
                                        </div>
                                        <span className="text-gray-400">
                                            {isHistoryOpen ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                                        </span>
                                    </button>

                                    {isHistoryOpen && (
                                        <div className="space-y-2.5">
                                            {completedAssignments.map((task) => (
                                                <div
                                                    key={task.id}
                                                    className="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-3.5 shadow-xs space-y-2"
                                                >
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div className="space-y-1">
                                                            <div className="flex items-center gap-1.5">
                                                                <span className="inline-flex items-center gap-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                                    <CheckCircle2 className="h-2.5 w-2.5" />
                                                                    Selesai
                                                                </span>
                                                                <h4 className="font-semibold text-xs text-gray-700 dark:text-gray-400 line-through">
                                                                    {task.title}
                                                                </h4>
                                                            </div>
                                                            {task.description && (
                                                                <p className="text-[11px] text-gray-400 dark:text-gray-500 line-clamp-1">
                                                                    {task.description}
                                                                </p>
                                                            )}
                                                        </div>

                                                        <div className="flex items-center gap-1 shrink-0">
                                                            {/* Tombol Kembalikan ke Tugas Aktif */}
                                                            <button
                                                                type="button"
                                                                disabled={processingTaskId === task.id}
                                                                onClick={() => toggleAssignmentStatus(task)}
                                                                title="Kembalikan ke Tugas Aktif"
                                                                className="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 rounded transition border border-gray-200 dark:border-gray-700 disabled:opacity-50"
                                                            >
                                                                <RotateCcw className="h-3 w-3" />
                                                                <span>{processingTaskId === task.id ? 'Menyimpan...' : 'Batal Selesai'}</span>
                                                            </button>

                                                            {/* Hapus Permanen */}
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    if (confirm('Hapus permanen tugas ini dari riwayat?')) {
                                                                        router.delete(route('assignments.destroy', task.id), {
                                                                            preserveScroll: true,
                                                                        });
                                                                    }
                                                                }}
                                                                title="Hapus Tugas"
                                                                className="text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition p-1"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div className="text-[11px] text-gray-400 dark:text-gray-500 flex items-center justify-between pt-1 border-t border-gray-50 dark:border-gray-800">
                                                        <span>
                                                            Tenggat: {new Date(task.deadline).toLocaleDateString('id-ID', {
                                                                day: 'numeric',
                                                                month: 'short',
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            })} WIB
                                                        </span>
                                                        {task.submission_url && (
                                                            <a
                                                                href={task.submission_url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                                                            >
                                                                Link Tugas <ExternalLink className="h-2.5 w-2.5" />
                                                            </a>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal Tambah Materi */}
            {isMaterialModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 dark:bg-black/75 backdrop-blur-xs p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800 p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Tambah Materi Baru</h3>
                        <form onSubmit={submitMaterial} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Judul Materi *</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="Contoh: Slide MVC & Routing"
                                    value={materialForm.data.title}
                                    onChange={(e) => materialForm.setData('title', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Pertemuan Ke- *</label>
                                <input
                                    type="number"
                                    min="1"
                                    max="50"
                                    required
                                    placeholder="1"
                                    value={materialForm.data.meeting_number}
                                    onChange={(e) => {
                                        const val = e.target.value;
                                        materialForm.setData('meeting_number', val === '' ? '' : Math.max(1, parseInt(val, 10) || 1));
                                    }}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Upload Dokumen / Foto (PDF, Gambar PNG/JPG, PPT, DOCX, ZIP)</label>
                                <input
                                    type="file"
                                    accept=".pdf,.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.ppt,.pptx,.zip,.rar"
                                    onChange={(e) => materialForm.setData('file', e.target.files[0])}
                                    className="mt-1 block w-full text-xs text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 dark:file:bg-blue-950/60 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/60"
                                />
                                {materialForm.progress && (
                                    <div className="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden mt-2">
                                        <div
                                            className="bg-blue-600 h-1.5 rounded-full transition-all duration-200"
                                            style={{ width: `${materialForm.progress.percentage}%` }}
                                        />
                                        <p className="text-[11px] text-blue-600 dark:text-blue-400 font-medium text-right mt-1">
                                            Mengunggah: {materialForm.progress.percentage}%
                                        </p>
                                    </div>
                                )}
                                <p className="mt-1.5 text-[11px] text-gray-500 dark:text-gray-400 leading-tight">
                                    Mendukung berkas PDF, foto/gambar (PNG, JPG, JPEG), slide PPT, dokumen Word, dan arsip ZIP (Maks. 20MB).
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Link Eksternal (Drive / YouTube / Web)</label>
                                <input
                                    type="url"
                                    placeholder="https://..."
                                    value={materialForm.data.external_link}
                                    onChange={(e) => materialForm.setData('external_link', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    onClick={() => setIsMaterialModalOpen(false)}
                                    className="rounded-lg px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={materialForm.processing}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition"
                                >
                                    Simpan Materi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Edit Materi */}
            {isEditMaterialModalOpen && editingMaterial && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 dark:bg-black/75 backdrop-blur-xs p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800 p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Edit Materi Kuliah</h3>
                        <form onSubmit={submitEditMaterial} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Judul Materi *</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="Contoh: Slide Arsitektur Web"
                                    value={editMaterialForm.data.title}
                                    onChange={(e) => editMaterialForm.setData('title', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Pertemuan Ke- *</label>
                                <input
                                    type="number"
                                    min="1"
                                    max="50"
                                    required
                                    placeholder="1"
                                    value={editMaterialForm.data.meeting_number}
                                    onChange={(e) => {
                                        const val = e.target.value;
                                        editMaterialForm.setData('meeting_number', val === '' ? '' : Math.max(1, parseInt(val, 10) || 1));
                                    }}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Ganti Berkas / Foto (Opsional)
                                </label>
                                {editingMaterial.file_path && (
                                    <p className="text-[11px] text-gray-500 dark:text-gray-400 mb-1">
                                        Berkas saat ini tersimpan: <span className="font-semibold text-gray-700 dark:text-gray-200">{editingMaterial.file_path.split('/').pop()}</span>. Biarkan kosong jika tidak ingin mengganti berkas.
                                    </p>
                                )}
                                <input
                                    type="file"
                                    accept=".pdf,.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.ppt,.pptx,.zip,.rar"
                                    onChange={(e) => editMaterialForm.setData('file', e.target.files[0])}
                                    className="mt-1 block w-full text-xs text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 dark:file:bg-blue-950/60 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/60"
                                />
                                {editMaterialForm.progress && (
                                    <div className="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden mt-2">
                                        <div
                                            className="bg-blue-600 h-1.5 rounded-full transition-all duration-200"
                                            style={{ width: `${editMaterialForm.progress.percentage}%` }}
                                        />
                                        <p className="text-[11px] text-blue-600 dark:text-blue-400 font-medium text-right mt-1">
                                            Mengunggah: {editMaterialForm.progress.percentage}%
                                        </p>
                                    </div>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Link Eksternal (Drive / YouTube / Web)</label>
                                <input
                                    type="url"
                                    placeholder="https://..."
                                    value={editMaterialForm.data.external_link}
                                    onChange={(e) => editMaterialForm.setData('external_link', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Catatan / Keterangan</label>
                                <textarea
                                    rows="2"
                                    placeholder="Catatan tambahan untuk materi ini..."
                                    value={editMaterialForm.data.notes}
                                    onChange={(e) => editMaterialForm.setData('notes', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setIsEditMaterialModalOpen(false);
                                        setEditingMaterial(null);
                                    }}
                                    className="rounded-lg px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={editMaterialForm.processing}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition"
                                >
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Tambah Tugas */}
            {isAssignmentModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 dark:bg-black/75 backdrop-blur-xs p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800 p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Tambah Tugas Kuliah</h3>
                        <form onSubmit={submitAssignment} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Nama Tugas *</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="Contoh: Tugas 1 - Skema Database"
                                    value={assignmentForm.data.title}
                                    onChange={(e) => assignmentForm.setData('title', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Tenggat Waktu (Deadline) *</label>
                                <input
                                    type="datetime-local"
                                    required
                                    value={assignmentForm.data.deadline}
                                    onChange={(e) => assignmentForm.setData('deadline', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Link Pengumpulan (Google Form / LMS)</label>
                                <input
                                    type="url"
                                    placeholder="https://forms.gle/... atau link LMS"
                                    value={assignmentForm.data.submission_url}
                                    onChange={(e) => assignmentForm.setData('submission_url', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Deskripsi / Instruksi</label>
                                <textarea
                                    rows="3"
                                    placeholder="Format berkas PDF, cantumkan NIM di lembar jawaban..."
                                    value={assignmentForm.data.description}
                                    onChange={(e) => assignmentForm.setData('description', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    onClick={() => setIsAssignmentModalOpen(false)}
                                    className="rounded-lg px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={assignmentForm.processing}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition"
                                >
                                    Simpan Tugas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Edit Mata Kuliah */}
            {isEditCourseModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 dark:bg-black/75 backdrop-blur-xs p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800 p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Edit Data Mata Kuliah</h3>
                        <form onSubmit={submitEditCourse} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Nama Mata Kuliah *</label>
                                <input
                                    type="text"
                                    required
                                    value={editCourseForm.data.name}
                                    onChange={(e) => editCourseForm.setData('name', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                {editCourseForm.errors.name && (
                                    <p className="text-xs text-red-500 mt-1">{editCourseForm.errors.name}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Kode MK</label>
                                    <input
                                        type="text"
                                        placeholder="Contoh: IF-302"
                                        value={editCourseForm.data.code}
                                        onChange={(e) => editCourseForm.setData('code', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Hari Kuliah</label>
                                    <select
                                        value={editCourseForm.data.day_of_week}
                                        onChange={(e) => editCourseForm.setData('day_of_week', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 cursor-pointer"
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
                                    value={editCourseForm.data.lecturer_name}
                                    onChange={(e) => editCourseForm.setData('lecturer_name', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    onClick={() => setIsEditCourseModalOpen(false)}
                                    className="rounded-lg px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={editCourseForm.processing}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition"
                                >
                                    {editCourseForm.processing ? 'Menyimpan...' : 'Perbarui Mata Kuliah'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Edit Tugas Kuliah */}
            {editingAssignment && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 dark:bg-black/75 backdrop-blur-xs p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800 p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Edit Tugas Kuliah</h3>
                        <form onSubmit={submitEditAssignment} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Nama Tugas *</label>
                                <input
                                    type="text"
                                    required
                                    value={editAssignmentForm.data.title}
                                    onChange={(e) => editAssignmentForm.setData('title', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                {editAssignmentForm.errors.title && (
                                    <p className="text-xs text-red-500 mt-1">{editAssignmentForm.errors.title}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Tenggat Waktu (Deadline) *</label>
                                <input
                                    type="datetime-local"
                                    required
                                    value={editAssignmentForm.data.deadline}
                                    onChange={(e) => editAssignmentForm.setData('deadline', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                {editAssignmentForm.errors.deadline && (
                                    <p className="text-xs text-red-500 mt-1">{editAssignmentForm.errors.deadline}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Link Pengumpulan (Google Form / LMS)</label>
                                <input
                                    type="url"
                                    placeholder="https://forms.gle/... atau link LMS"
                                    value={editAssignmentForm.data.submission_url}
                                    onChange={(e) => editAssignmentForm.setData('submission_url', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">Deskripsi / Instruksi</label>
                                <textarea
                                    rows="3"
                                    placeholder="Format berkas PDF, cantumkan NIM di lembar jawaban..."
                                    value={editAssignmentForm.data.description}
                                    onChange={(e) => editAssignmentForm.setData('description', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    onClick={() => setEditingAssignment(null)}
                                    className="rounded-lg px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={editAssignmentForm.processing}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition"
                                >
                                    {editAssignmentForm.processing ? 'Menyimpan...' : 'Perbarui Tugas'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Pratinjau Dokumen / Slide Materi */}
            {previewMaterial && (() => {
                const fileInfo = getFileInfo(previewMaterial.file_path);
                return (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-2 sm:p-4">
                        <div className="flex flex-col w-full max-w-5xl h-[90vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
                            {/* Modal Header */}
                            <div className="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 dark:border-gray-800 bg-gray-50/90 dark:bg-gray-850/90">
                                <div className="flex items-center gap-3 min-w-0">
                                    <div className={`p-2 rounded-lg shrink-0 ${fileInfo.iconBg}`}>
                                        <FileText className="h-5 w-5" />
                                    </div>
                                    <div className="truncate">
                                        <div className="flex flex-wrap items-center gap-2">
                                            {previewMaterial.meeting_number && (
                                                <span className="text-[11px] font-bold px-2 py-0.5 bg-blue-100 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 rounded-full border dark:border-blue-900/60">
                                                    Pertemuan {previewMaterial.meeting_number}
                                                </span>
                                            )}
                                            <h3 className="font-bold text-gray-900 dark:text-white text-base truncate">
                                                {previewMaterial.title}
                                            </h3>
                                            {fileInfo.ext && (
                                                <span className={`text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider ${fileInfo.badgeClass}`}>
                                                    {fileInfo.ext}
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {previewMaterial.notes && (
                                                <span className="truncate">{previewMaterial.notes}</span>
                                            )}
                                            {previewMaterial.notes && <span>•</span>}
                                            <span className="flex items-center gap-1 shrink-0">
                                                <Clock className="h-3 w-3 text-gray-400 dark:text-gray-500" />
                                                Diinput: {formatTimestamp(previewMaterial.created_at)}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center gap-2 shrink-0">
                                    {fileInfo.canPreviewInline && (
                                        <a
                                            href={route('materials.preview', previewMaterial.id)}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-750 shadow-sm transition"
                                            title="Buka dokumen di tab baru browser"
                                        >
                                            <ExternalLink className="h-3.5 w-3.5 text-gray-500 dark:text-gray-400" />
                                            <span className="hidden sm:inline">Tab Baru</span>
                                        </a>
                                    )}
                                    <a
                                        href={route('materials.download', previewMaterial.id)}
                                        className="inline-flex items-center gap-1 text-xs font-semibold text-white bg-blue-600 px-3 py-2 rounded-lg hover:bg-blue-700 shadow-sm transition"
                                        title="Unduh berkas"
                                    >
                                        <Download className="h-3.5 w-3.5" />
                                        <span className="hidden sm:inline">Unduh</span>
                                    </a>
                                    <button
                                        onClick={() => setPreviewMaterial(null)}
                                        className="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-200/60 dark:hover:bg-gray-800 p-2 rounded-lg transition"
                                        title="Tutup pratinjau"
                                    >
                                        <X className="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            {/* Modal Body / Viewer */}
                            {fileInfo.canPreviewInline ? (
                                fileInfo.type === 'image' ? (
                                    <div className="flex-1 bg-gray-950 p-4 flex items-center justify-center overflow-auto">
                                        <img
                                            src={route('materials.preview', previewMaterial.id)}
                                            alt={previewMaterial.title}
                                            className="max-h-full max-w-full object-contain rounded-lg shadow-xl"
                                        />
                                    </div>
                                ) : (
                                    <div className="flex-1 bg-gray-100 dark:bg-gray-950 p-1 sm:p-2 overflow-hidden">
                                        <iframe
                                            src={route('materials.preview', previewMaterial.id)}
                                            className="w-full h-full rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-inner"
                                            title={previewMaterial.title}
                                        />
                                    </div>
                                )
                            ) : (
                                <div className="flex-1 bg-slate-50 dark:bg-gray-950 flex items-center justify-center p-6 sm:p-10 overflow-y-auto">
                                    <div className="max-w-md w-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-md p-6 sm:p-8 text-center space-y-5">
                                        <div className={`mx-auto w-16 h-16 rounded-2xl flex items-center justify-center shadow-inner ${fileInfo.iconBg}`}>
                                            <FileText className="w-8 h-8" />
                                        </div>

                                        <div className="space-y-1.5">
                                            <span className={`inline-block text-[11px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider ${fileInfo.badgeClass}`}>
                                                Format: {fileInfo.ext}
                                            </span>
                                            <h4 className="text-lg font-bold text-gray-900 dark:text-white">
                                                {previewMaterial.title}
                                            </h4>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {previewMaterial.meeting_number ? `Pertemuan ${previewMaterial.meeting_number} • ` : ''}
                                                Diinput pada {formatTimestamp(previewMaterial.created_at)}
                                            </p>
                                        </div>

                                        <div className="bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/60 rounded-xl p-4 text-left space-y-1.5">
                                            <div className="flex items-center gap-1.5 font-bold text-amber-900 dark:text-amber-300 text-xs">
                                                <AlertCircle className="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                                                Pratinjau Langsung Tidak Didukung Browser
                                            </div>
                                            <p className="leading-relaxed text-amber-800/90 dark:text-amber-300/80 text-xs">
                                                Browser tidak dapat membaca berkas <strong>.{fileInfo.ext.toLowerCase()}</strong> secara langsung tanpa aplikasi Office terinstal. Silakan unduh berkas untuk membukanya di laptop/PC Anda.
                                            </p>
                                        </div>

                                        <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2.5">
                                            <a
                                                href={route('materials.download', previewMaterial.id)}
                                                className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-xs text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition"
                                            >
                                                <Download className="h-4 w-4" />
                                                Unduh Berkas ({fileInfo.ext})
                                            </a>
                                            <button
                                                type="button"
                                                onClick={() => setPreviewMaterial(null)}
                                                className="w-full sm:w-auto px-4 py-2.5 rounded-xl font-semibold text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                            >
                                                Tutup
                                            </button>
                                        </div>

                                        <div className="border-t border-gray-100 dark:border-gray-800 pt-3 text-[11px] text-gray-500 dark:text-gray-400 leading-normal">
                                            <strong>Rekomendasi:</strong> Bila ingin materi bisa langsung dibaca di dalam browser seperti slide digital, simpan dokumen Anda sebagai <strong>PDF</strong> sebelum diunggah.
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                );
            })()}
        </AuthenticatedLayout>
    );
}