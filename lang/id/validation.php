<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut memuat pesan kesalahan standar yang digunakan oleh
    | kelas validator. Beberapa aturan memiliki beberapa versi seperti aturan ukuran.
    |
    */

    'accepted'             => ':attribute harus diterima.',
    'active_url'           => ':attribute bukan URL yang valid.',
    'after'                => ':attribute harus berisi tanggal setelah :date.',
    'after_or_equal'       => ':attribute harus berisi tanggal setelah atau sama dengan :date.',
    'alpha'                => ':attribute hanya boleh berisi huruf.',
    'alpha_dash'           => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num'            => ':attribute hanya boleh berisi huruf dan angka.',
    'array'                => ':attribute harus berupa sebuah array.',
    'before'               => ':attribute harus berisi tanggal sebelum :date.',
    'before_or_equal'      => ':attribute harus berisi tanggal sebelum atau sama dengan :date.',
    'between'              => [
        'numeric' => ':attribute harus bernilai antara :min sampai :max.',
        'file'    => ':attribute harus berukuran antara :min sampai :max kilobita.',
        'string'  => ':attribute harus berisi antara :min sampai :max karakter.',
        'array'   => ':attribute harus memiliki :min sampai :max anggota.',
    ],
    'boolean'              => ':attribute harus bernilai true atau false.',
    'confirmed'            => 'Konfirmasi :attribute tidak cocok.',
    'current_password'     => 'Kata sandi saat ini salah.',
    'date'                 => ':attribute bukan tanggal yang valid.',
    'date_equals'          => ':attribute harus berisi tanggal yang sama dengan :date.',
    'date_format'          => ':attribute tidak cocok dengan format :format.',
    'different'            => ':attribute dan :other harus berbeda.',
    'digits'               => ':attribute harus terdiri dari :digits angka.',
    'digits_between'       => ':attribute harus berukuran antara :min sampai :max angka.',
    'email'                => ':attribute harus berupa alamat email yang valid.',
    'ends_with'            => ':attribute harus diakhiri salah satu dari berikut: :values.',
    'exists'               => ':attribute yang dipilih tidak valid.',
    'file'                 => ':attribute harus berupa sebuah berkas.',
    'filled'               => ':attribute wajib diisi.',
    'gt'                   => [
        'numeric' => ':attribute harus bernilai lebih besar dari :value.',
        'file'    => ':attribute harus berukuran lebih besar dari :value kilobita.',
        'string'  => ':attribute harus berisi lebih dari :value karakter.',
        'array'   => ':attribute harus memiliki lebih dari :value anggota.',
    ],
    'gte'                  => [
        'numeric' => ':attribute harus bernilai lebih besar dari atau sama dengan :value.',
        'file'    => ':attribute harus berukuran lebih besar dari atau sama dengan :value kilobita.',
        'string'  => ':attribute harus berisi minimal :value karakter.',
        'array'   => ':attribute harus mempunyai :value anggota atau lebih.',
    ],
    'image'                => ':attribute harus berupa gambar.',
    'in'                   => ':attribute yang dipilih tidak valid.',
    'integer'              => ':attribute harus berupa bilangan bulat.',
    'lowercase'            => ':attribute harus berupa huruf kecil.',
    'max'                  => [
        'numeric' => ':attribute maksimal bernilai :max.',
        'file'    => ':attribute maksimal berukuran :max kilobita.',
        'string'  => ':attribute maksimal berisi :max karakter.',
        'array'   => ':attribute maksimal memiliki :max anggota.',
    ],
    'mimes'                => ':attribute harus berupa berkas bertipe: :values.',
    'mimetypes'            => ':attribute harus berupa berkas bertipe: :values.',
    'min'                  => [
        'numeric' => ':attribute minimal bernilai :min.',
        'file'    => ':attribute minimal berukuran :min kilobita.',
        'string'  => ':attribute minimal berisi :min karakter.',
        'array'   => ':attribute minimal memiliki :min anggota.',
    ],
    'not_in'               => ':attribute yang dipilih tidak valid.',
    'numeric'              => ':attribute harus berupa angka.',
    'present'              => ':attribute wajib ada.',
    'regex'                => 'Format :attribute tidak valid.',
    'required'             => ':attribute wajib diisi.',
    'required_if'          => ':attribute wajib diisi bila :other adalah :value.',
    'required_unless'      => ':attribute wajib diisi kecuali :other memiliki nilai :values.',
    'required_with'        => ':attribute wajib diisi bila terdapat :values.',
    'required_without'     => ':attribute wajib diisi bila tidak terdapat :values.',
    'same'                 => ':attribute dan :other harus sama.',
    'size'                 => [
        'numeric' => ':attribute harus berukuran :size.',
        'file'    => ':attribute harus berukuran :size kilobita.',
        'string'  => ':attribute harus berukuran :size karakter.',
        'array'   => ':attribute harus mengandung :size anggota.',
    ],
    'string'               => ':attribute harus berupa string.',
    'timezone'             => ':attribute harus berupa zona waktu yang valid.',
    'unique'               => ':attribute sudah terdaftar.',
    'uploaded'             => ':attribute gagal diunggah.',
    'url'                  => 'Format :attribute tidak valid.',

    /*
    |--------------------------------------------------------------------------
    | Atribut Kustom Bahasa Indonesia
    |--------------------------------------------------------------------------
    |
    | Baris-baris bahasa berikut digunakan untuk menukar atribut placeholder
    | dengan nama yang lebih ramah pengguna seperti 'Alamat Email' bukan 'email'.
    |
    */

    'attributes' => [
        'name'                  => 'nama lengkap',
        'email'                 => 'alamat email',
        'password'              => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'current_password'      => 'kata sandi saat ini',
        'code'                  => 'kode mata kuliah',
        'lecturer'              => 'nama dosen',
        'day'                   => 'hari kuliah',
        'start_time'            => 'jam mulai',
        'end_time'              => 'jam selesai',
        'room'                  => 'ruangan kuliah',
        'color'                 => 'label warna',
        'title'                 => 'judul',
        'meeting_number'        => 'pertemuan ke',
        'file'                  => 'berkas',
        'due_date'              => 'tenggat waktu',
        'link'                  => 'tautan pengumpulan',
        'notes'                 => 'catatan',
        'whatsapp_number'       => 'nomor WhatsApp',
        'telegram_chat_id'      => 'ID Chat Telegram',
    ],

];
