<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\AlumniFeedback;
use App\Models\Career;
use App\Models\Competency;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\LearningStage;
use App\Models\Media;
use App\Models\Partner;
use App\Models\Program;
use App\Models\Staff;
use Illuminate\Database\Seeder;

/**
 * Konten landing publik TSM SMKS Islam 1 Kota Blitar.
 *
 * Melengkapi CmsTsmSeeder: media placeholder (SVG lokal), galeri, prestasi,
 * staf, kompetensi, program, sarana, karier, mitra, dan FAQ.
 * Idempotent — aman dijalankan berulang (firstOrCreate / updateOrCreate).
 */
class LandingContentSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------- media placeholder (SVG lokal, tanpa internet) ----------------
        $media = [
            ['path' => 'placeholders/bengkel1.svg', 'alt' => 'Bengkel praktik TSM', 'caption' => 'Ruangan bengkel praktik mesin sepeda motor jurusan TSM.'],
            ['path' => 'placeholders/bengkel2.svg', 'alt' => 'Bengkel sekolah', 'caption' => 'Area bengkel sekolah tempat siswa latihan servis.'],
            ['path' => 'placeholders/sekolah1.svg', 'alt' => 'Gedung sekolah TSM', 'caption' => 'Gedung dan halaman SMKS Islam 1 Kota Blitar.'],
            ['path' => 'placeholders/motor1.svg', 'alt' => 'Sepeda motor praktik', 'caption' => 'Unit sepeda motor yang dipakai siswa untuk praktik.'],
            ['path' => 'placeholders/motor2.svg', 'alt' => 'Unit motor injeksi', 'caption' => 'Unit sepeda motor injeksi untuk materi EFI.'],
            ['path' => 'placeholders/praktik1.svg', 'alt' => 'Praktik servis', 'caption' => 'Siswa melakukan praktik servis mesin sepeda motor.'],
            ['path' => 'placeholders/praktik2.svg', 'alt' => 'Tune up mesin', 'caption' => 'Praktik tune up dan perawatan mesin di bengkel sekolah.'],
            ['path' => 'placeholders/tim1.svg', 'alt' => 'Guru dan instruktur TSM', 'caption' => 'Guru dan instruktur bengkel jurusan TSM.'],
        ];

        $mediaIds = [];
        foreach ($media as $item) {
            $abs = storage_path('app/public/' . $item['path']);
            $size = is_file($abs) ? filesize($abs) : 0;

            $row = Media::firstOrCreate(
                ['path' => $item['path']],
                [
                    'disk' => 'public',
                    'mime' => 'image/svg+xml',
                    'size' => $size,
                    'width' => 1200,
                    'height' => 800,
                    'alt' => $item['alt'],
                    'caption' => $item['caption'],
                ]
            );

            $mediaIds[basename($item['path'])] = $row->id;
        }

        $m = fn (string $key) => $mediaIds[$key] ?? null;

        // ---------------- galeri ----------------
        $gallery = [
            ['media' => 'bengkel1.svg', 'caption' => 'Praktik servis mesin sepeda motor', 'album' => 'bengkel'],
            ['media' => 'praktik1.svg', 'caption' => 'Siswa melakukan tune up di bengkel sekolah', 'album' => 'bengkel'],
            ['media' => 'motor1.svg', 'caption' => 'Unit sepeda motor praktik siswa', 'album' => 'bengkel'],
            ['media' => 'praktik2.svg', 'caption' => 'Praktik perawatan sistem injeksi (EFI)', 'album' => 'bengkel'],
            ['media' => 'sekolah1.svg', 'caption' => 'Gedung jurusan Teknik Sepeda Motor', 'album' => 'sekolah'],
            ['media' => 'tim1.svg', 'caption' => 'Guru dan instruktur bengkel TSM', 'album' => 'sekolah'],
        ];

        foreach ($gallery as $i => $item) {
            GalleryItem::firstOrCreate(
                ['media_id' => $m($item['media']), 'caption' => $item['caption']],
                ['album' => $item['album'], 'sort' => $i + 1]
            );
        }

        // ---------------- prestasi ----------------
        $achievements = [
            ['title' => 'Juara 2 LKS Tingkat Kota Blitar', 'level' => 'Kota', 'rank' => 'Juara 2', 'year' => 2025, 'student_name' => 'Rizky Pratama', 'description' => 'Berhasil menyelesaikan diagnosis kerusakan mesin dan perawatan transmisi dalam Lomba Kompetensi Siswa bidang sepeda motor.', 'image' => 'bengkel1.svg'],
            ['title' => 'Juara 1 Kompetisi Tuning Sepeda Motor', 'level' => 'Provinsi', 'rank' => 'Juara 1', 'year' => 2025, 'student_name' => 'Ahmad Fauzi', 'description' => 'Meraih juara pertama pada lomba tuning mesin tingkat Provinsi Jawa Timur.', 'image' => 'praktik2.svg'],
            ['title' => 'Juara 3 LKS Nasional', 'level' => 'Nasional', 'rank' => 'Juara 3', 'year' => 2024, 'student_name' => 'Bagas Setiawan', 'description' => 'Mewakili sekolah di Lomba Kompetensi Siswa tingkat nasional bidang teknik sepeda motor.', 'image' => 'motor2.svg'],
            ['title' => 'Juara 1 Poster Keamanan Berkendara', 'level' => 'Kota', 'rank' => 'Juara 1', 'year' => 2024, 'student_name' => 'Nadia Putri', 'description' => 'Kampanye keselamatan berkendara lewat karya poster yang dinilai paling komunikatif.', 'image' => 'sekolah1.svg'],
            ['title' => 'Juara 2 Modifikasi Motor', 'level' => 'Provinsi', 'rank' => 'Juara 2', 'year' => 2024, 'student_name' => 'Yoga Saputra', 'description' => 'Karya modifikasi sepeda motor yang mengutamakan fungsi, keamanan, dan estetika.', 'image' => 'motor1.svg'],
            ['title' => 'Juara 1 Cipta Inovasi Teknik Otomotif', 'level' => 'Nasional', 'rank' => 'Juara 1', 'year' => 2025, 'student_name' => 'Fajar Nugroho', 'description' => 'Inovasi alat bantu praktik otomotif yang mempermudah proses servis di bengkel.', 'image' => 'praktik1.svg'],
        ];

        foreach ($achievements as $i => $item) {
            Achievement::firstOrCreate(
                ['title' => $item['title'], 'year' => $item['year']],
                [
                    'level' => $item['level'],
                    'rank' => $item['rank'],
                    'student_name' => $item['student_name'],
                    'description' => $item['description'],
                    'image_id' => $m($item['image']),
                    'sort' => $i + 1,
                    'is_visible' => true,
                ]
            );
        }

        // ---------------- staf & instruktur ----------------
        $staff = [
            ['name' => 'Pak Andi Wijaya', 'role' => 'Kepala Program Keahlian', 'bio' => 'Mengelola arah pembelajaran jurusan TSM dan kerja sama dengan bengkel mitra industri.', 'photo' => 'tim1.svg'],
            ['name' => 'Bu Siti Rahayu', 'role' => 'Guru Produktif Mesin', 'bio' => 'Mengajar materi mesin sepeda motor dan mendampingi siswa saat praktik di bengkel.', 'photo' => 'bengkel1.svg'],
            ['name' => 'Pak Budi Santoso', 'role' => 'Instruktur Bengkel', 'bio' => 'Instruktur bengkel yang membimbing siswa pada perawatan dan servis harian.', 'photo' => 'bengkel2.svg'],
            ['name' => 'Bu Dewi Lestari', 'role' => 'Guru Matematika', 'bio' => 'Mengajar matematika terapan yang dipakai untuk perhitungan mesin dan kelistrikan.', 'photo' => 'sekolah1.svg'],
            ['name' => 'Pak Eko Prasetyo', 'role' => 'Instruktur Chasis', 'bio' => 'Membimbing praktik sistem chasis, rem, dan suspensi sepeda motor.', 'photo' => 'praktik1.svg'],
        ];

        foreach ($staff as $i => $item) {
            Staff::firstOrCreate(
                ['name' => $item['name']],
                [
                    'role' => $item['role'],
                    'bio' => $item['bio'],
                    'photo_id' => $m($item['photo']),
                    'sort' => $i + 1,
                ]
            );
        }

        // ---------------- kompetensi ----------------
        $competencies = [
            ['title' => 'Perawatan Mesin Sepeda Motor', 'description' => 'Servis rutin, penggantian oli, penyetelan klep, dan perawatan bagian mesin sesuai standar bengkel.', 'icon' => 'wrench', 'highlight' => true],
            ['title' => 'Perbaikan Sistem Kelistrikan', 'description' => 'Menelusuri dan memperbaiki gangguan lampu, pengisian, starter, dan sistem kelistrikan motor.', 'icon' => 'zap', 'highlight' => false],
            ['title' => 'Tune Up & Injeksi (EFI)', 'description' => 'Melakukan tune up mesin injeksi, membaca kode gangguan, dan menyetel performa mesin.', 'icon' => 'gauge', 'highlight' => false],
            ['title' => 'Diagnosis & Troubleshooting', 'description' => 'Menganalisis gejala kerusakan lalu menentukan tindakan perbaikan yang tepat dan efisien.', 'icon' => 'search', 'highlight' => false],
        ];

        foreach ($competencies as $i => $item) {
            Competency::firstOrCreate(
                ['title' => $item['title']],
                [
                    'description' => $item['description'],
                    'icon' => $item['icon'],
                    'sort' => $i + 1,
                    'is_highlight' => $item['highlight'],
                    'is_visible' => true,
                ]
            );
        }

        // ---------------- program ----------------
        $programs = [
            ['title' => 'Kelas Reguler 3 Tahun', 'description' => 'Pembelajaran kelas X sampai XII dengan porsi praktik bengkel lebih banyak daripada teori.', 'image' => 'sekolah1.svg'],
            ['title' => 'Praktik Industri (Prakerin)', 'description' => 'Siswa magang di bengkel resmi dan mitra industri untuk merasakan kerja sungguhan.', 'image' => 'bengkel2.svg'],
            ['title' => 'Sertifikasi Kompetensi BNSP', 'description' => 'Uji kompetensi resmi agar lulusan memiliki sertifikat keahlian yang diakui industri.', 'image' => 'praktik2.svg'],
        ];

        foreach ($programs as $i => $item) {
            Program::firstOrCreate(
                ['title' => $item['title']],
                [
                    'description' => $item['description'],
                    'image_id' => $m($item['image']),
                    'sort' => $i + 1,
                    'is_visible' => true,
                ]
            );
        }

        // ---------------- sarana & prasarana ----------------
        $facilities = [
            ['name' => 'Bengkel Praktik Mesin', 'spec' => 'Meja kerja mesin sepeda motor', 'quantity' => 8],
            ['name' => 'Ruang Teori ber-AC', 'spec' => 'Ruang kelas dengan pendingin udara', 'quantity' => 6],
            ['name' => 'Laboratorium Kelistrikan', 'spec' => 'Meja uji kelistrikan dan alat ukur', 'quantity' => 4],
            ['name' => 'Unit Praktik Sepeda Motor', 'spec' => 'Unit sepeda motor siap praktik', 'quantity' => 12],
            ['name' => 'Ruang Komputer', 'spec' => 'Komputer untuk materi dan administrasi', 'quantity' => 2],
            ['name' => 'Sekolah Area Luas', 'spec' => 'Halaman luas untuk praktik dan upacara', 'quantity' => 1],
        ];

        foreach ($facilities as $i => $item) {
            Facility::firstOrCreate(
                ['name' => $item['name']],
                [
                    'spec' => $item['spec'],
                    'quantity' => $item['quantity'],
                    'sort' => $i + 1,
                    'is_visible' => true,
                ]
            );
        }

        // ---------------- prospek karier ----------------
        $careers = [
            'Mekanik Bengkel Resmi',
            'Teknisi Service Motor',
            'Wirausaha Bengkel Motor',
            'Quality Control Otomotif',
            'Konsultan Spare Part',
        ];

        foreach ($careers as $i => $label) {
            Career::firstOrCreate(['label' => $label], ['sort' => $i + 1]);
        }

        // ---------------- mitra industri ----------------
        $partners = [
            'Bengkel Maju Jaya Motor',
            'Toko Sumber Makmur',
            'Dealer Honda Blitar',
            'Bengkel Yamaha Sentosa',
            'CV Karya Teknik',
        ];

        foreach ($partners as $i => $name) {
            Partner::firstOrCreate(['name' => $name], ['sort' => $i + 1]);
        }

        // ---------------- FAQ ----------------
        $faqs = [
            ['question' => 'Berapa lama masa belajar?', 'answer' => 'Masa belajar jurusan TSM adalah tiga tahun, dari kelas X sampai kelas XII. Sebagian besar waktu dipakai untuk praktik di bengkel sekolah dan praktik industri di bengkel mitra.'],
            ['question' => 'Apa syarat pendaftaran?', 'answer' => 'Calon siswa cukup mengisi formulir pendaftaran dengan melampirkan fotokopi ijazah atau SKL SMP, kartu keluarga, dan pas foto. Pendaftaran dibuka untuk lulusan SMP sederajat tanpa syarat khusus bidang otomotif.'],
            ['question' => 'Apakah ada praktik kerja industri?', 'answer' => 'Ada. Siswa kelas XI mengikuti Prakerin di bengkel resmi dan bengkel mitra sekolah selama beberapa bulan. Kegiatan ini dinilai oleh instruktur sekolah dan pembimbing bengkel.'],
            ['question' => 'Berapa biaya pendaftaran?', 'answer' => 'Biaya pendaftaran dan rincian biaya sekolah dapat ditanyakan langsung ke bagian pendaftaran melalui WhatsApp atau datang ke sekolah. Tersedia informasi keringanan bagi siswa yang membutuhkan.'],
            ['question' => 'Apakah lulusan tersertifikasi?', 'answer' => 'Ya. Sebelum lulus, siswa mengikuti uji kompetensi bersertifikat BNSP pada bidang sepeda motor. Sertifikat ini diakui industri sehingga memudahkan lulusan masuk dunia kerja.'],
        ];

        foreach ($faqs as $i => $item) {
            Faq::firstOrCreate(
                ['question' => $item['question']],
                ['answer' => $item['answer'], 'sort' => $i + 1]
            );
        }

        // ---------------- alur belajar (kelas X, XI, XII) ----------------
        $stages = [
            [
                'class_label' => 'X',
                'title' => 'Dasar sepeda motor',
                'description' => 'Kenalan dengan komponen mesin, sistem kelistrikan, dan keselamatan kerja. Siswa belajar membaca manual servis serta memakai alat ukur dengan benar sebelum masuk ke pekerjaan nyata.',
            ],
            [
                'class_label' => 'XI',
                'title' => 'Perawatan dan perbaikan',
                'description' => 'Latihan perawatan berkala, service injeksi, perbaikan kelistrikan, dan overhaul mesin ringan di bengkel sekolah. Di akhir tahun siswa mengikuti Prakerin di bengkel mitra.',
            ],
            [
                'class_label' => 'XII',
                'title' => 'Kompetensi dan uji sertifikasi',
                'description' => 'Pengerjaan perbaikan penuh dengan standar bengkel resmi, praktik kerja nyata, lalu persiapan uji kompetensi BNSP agar lulusan langsung diterima di industri.',
            ],
        ];

        foreach ($stages as $i => $stage) {
            LearningStage::firstOrCreate(
                ['class_label' => $stage['class_label']],
                [
                    'title' => $stage['title'],
                    'description' => $stage['description'],
                    'sort' => $i + 1,
                ]
            );
        }

        // ---------------- umpan balik alumni ----------------
        // ditampilkan di landing setelah disetujui (status: approved)
        $feedbacks = [
            [
                'name' => 'Ahmad Fauzi',
                'graduation_year' => 2023,
                'occupation' => 'Mekanik bengkel resmi Honda',
                'message' => 'Pelajaran yang paling kepakai adalah latihan service injeksi di kelas XI. Begitu masuk bengkel resmi, saya sudah terbiasa membaca alat scan dan menangani keluhan pelanggan.',
            ],
            [
                'name' => 'Rizky Maulana',
                'graduation_year' => 2022,
                'occupation' => 'Pemilik bengkel sendiri',
                'message' => 'Saya berani buka bengkel kecil setelah lulus karena selama sekolah sudah terbiasa menangani perbaikan sendiri, dari blok mesin sampai sistem kelistrikan.',
            ],
            [
                'name' => 'Dwi Lestari',
                'graduation_year' => 2024,
                'occupation' => 'Administrasi dealer motor',
                'message' => 'Ilmu servisnya tetap saya pakai untuk memahami keluhan pelanggan. Di tempat kerja, saya jadi lebih paham teknis ketika menjelaskan biaya perawatan kepada pelanggan.',
            ],
            [
                'name' => 'Bagus Setiawan',
                'graduation_year' => 2021,
                'occupation' => 'Mekanik bengkel Yamaha',
                'message' => 'Uji kompetensi BNSP sangat membantu. Sertifikat itu yang membuat saya lolos tahap pertama saat melamar di bengkel resmi tanpa harus menunggu lama.',
            ],
        ];

        foreach ($feedbacks as $item) {
            AlumniFeedback::firstOrCreate(
                [
                    'name' => $item['name'],
                    'graduation_year' => $item['graduation_year'],
                ],
                [
                    'occupation' => $item['occupation'],
                    'message' => $item['message'],
                    'status' => 'approved',
                ]
            );
        }
    }
}
