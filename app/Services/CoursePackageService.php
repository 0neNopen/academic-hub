<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;

class CoursePackageService
{
    /**
     * Daftar preset mata kuliah semester 3 Kelas B D3 Teknik Informatika
     */
    public static function getD3TICourses(): array
    {
        return [
            // SENIN
            [
                'name' => 'Manajemen Proyek Teknologi Informasi',
                'lecturer_name' => 'Dr. Ovide Decroly Wisnu Ardhi, S.T., M.Eng.',
                'room' => 'TEFA',
                'day_of_week' => 'Senin',
                'start_time' => '12:05',
                'end_time' => '14:45',
                'color' => '#3b82f6', // Biru
            ],
            [
                'name' => 'Interaksi Manusia dan Komputer',
                'lecturer_name' => 'Yudho Yudhanto, S.Kom., M.Kom.',
                'room' => 'TEFA',
                'day_of_week' => 'Senin',
                'start_time' => '14:50',
                'end_time' => '17:30',
                'color' => '#6366f1', // Indigo
            ],

            // SELASA
            [
                'name' => 'Aplikasi Mobile',
                'lecturer_name' => 'Sahirul Alim Tri Bawono, S.Kom., M.Eng.',
                'room' => 'Lab Mix Reality',
                'day_of_week' => 'Selasa',
                'start_time' => '07:30',
                'end_time' => '11:05',
                'color' => '#8b5cf6', // Ungu
            ],
            [
                'name' => 'Pemrograman Multimedia Interaktif',
                'lecturer_name' => 'Taufiqurrakhman Nur Hidayat, S.Kom., M.Cs.',
                'room' => 'Lab Mix Reality',
                'day_of_week' => 'Selasa',
                'start_time' => '11:10',
                'end_time' => '14:45',
                'color' => '#ec4899', // Pink
            ],

            // RABU
            [
                'name' => 'Pemrograman Front-end',
                'lecturer_name' => 'Yudho Yudhanto, S.Kom., M.Kom.',
                'room' => 'Lab Pemrograman',
                'day_of_week' => 'Rabu',
                'start_time' => '07:30',
                'end_time' => '11:05',
                'color' => '#0ea5e9', // Sky Blue
            ],
            [
                'name' => 'Pengujian Perangkat Lunak',
                'lecturer_name' => 'Taufiqurrakhman Nur Hidayat, S.Kom., M.Cs.',
                'room' => 'Lab Pemrograman',
                'day_of_week' => 'Rabu',
                'start_time' => '11:10',
                'end_time' => '12:55',
                'color' => '#14b8a6', // Teal
            ],
            [
                'name' => 'Etika dan Komunikasi di Dunia Kerja',
                'lecturer_name' => 'Ahimsa Adi Wibowo S.I.Kom., M.A.',
                'room' => 'Lab Pemrograman',
                'day_of_week' => 'Rabu',
                'start_time' => '13:00',
                'end_time' => '14:45',
                'color' => '#10b981', // Emerald
            ],
            [
                'name' => 'Kewirausahaan',
                'lecturer_name' => 'Dr. Fajar Danur Isnantyo S.T., M.Sc.',
                'room' => 'Lab Pemrograman',
                'day_of_week' => 'Rabu',
                'start_time' => '14:50',
                'end_time' => '16:35',
                'color' => '#eab308', // Amber
            ],

            // KAMIS
            [
                'name' => 'Internet of Things',
                'lecturer_name' => 'Nanang Maulana Yoeseph, S.Si., M.Cs.',
                'room' => 'Lab Jaringan',
                'day_of_week' => 'Kamis',
                'start_time' => '07:30',
                'end_time' => '10:10',
                'color' => '#06b6d4', // Cyan
            ],
            [
                'name' => 'Sistem Keamanan Data',
                'lecturer_name' => "Fiddin Yusfida A'la, S.T., M.Eng.",
                'room' => 'Lab RPL',
                'day_of_week' => 'Kamis',
                'start_time' => '10:15',
                'end_time' => '12:00',
                'color' => '#f43f5e', // Rose
            ],
            [
                'name' => 'Statistika Teknologi Informasi',
                'lecturer_name' => 'Fajar Dwi Cahyoko S.P., M.Stat.',
                'room' => 'Lab RPL',
                'day_of_week' => 'Kamis',
                'start_time' => '12:05',
                'end_time' => '13:50',
                'color' => '#f97316', // Orange
            ],
            [
                'name' => 'Pemrograman Back-end',
                'lecturer_name' => 'Nanang Maulana Yoeseph, S.Si., M.Cs.',
                'room' => 'Lab RPL',
                'day_of_week' => 'Kamis',
                'start_time' => '13:55',
                'end_time' => '17:30',
                'color' => '#64748b', // Slate
            ],
        ];
    }

    /**
     * Memasukkan paket mata kuliah D3 TI Semester 3 Kelas B ke user
     */
    public static function seedD3TI(User $user): void
    {
        $courses = self::getD3TICourses();

        foreach ($courses as $item) {
            $user->courses()->create($item);
        }
    }
}
