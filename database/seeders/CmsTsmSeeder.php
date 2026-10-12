<?php

namespace Database\Seeders;

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Post;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal TSM SMKS Islam 1 Kota Blitar.
 * Semua nilai adalah PLACEHOLDER — ganti dengan data asli sekolah.
 */
class CmsTsmSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------- users ----------------
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@tsm-blitar.sch.id'],
            [
                'name' => 'Admin TSM',
                'password' => Hash::make('admin12345'),
                'role' => Role::SuperAdmin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $editor = User::updateOrCreate(
            ['email' => 'editor@tsm-blitar.sch.id'],
            [
                'name' => 'Editor TSM',
                'password' => Hash::make('editor12345'),
                'role' => Role::Editor,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // ---------------- settings (key-value) ----------------
        $settings = [
            'site_name' => ['value' => 'Jurusan Teknik Sepeda Motor', 'group' => 'umum'],
            'site_full_name' => ['value' => 'SMKS Islam 1 Kota Blitar — Jurusan Teknik Sepeda Motor', 'group' => 'umum'],
            'school_address' => ['value' => 'Jl. Musi No. 06, Kota Blitar, Jawa Timur', 'group' => 'umum'],
            'school_whatsapp' => ['value' => '6281234567890', 'group' => 'umum'],
            'school_email' => ['value' => 'info@tsm-blitar.sch.id', 'group' => 'umum'],
            'service_hours' => ['value' => 'Senin–Sabtu, 07.00–15.00 WIB', 'group' => 'umum'],
            'tagline' => ['value' => 'Belajar servis sepeda motor sampai mahir, siap kerja dan siap buka bengkel.', 'group' => 'umum'],
            'cta_daftar_text' => ['value' => 'Daftar sekarang', 'group' => 'umum'],
            'footer_text' => ['value' => '© ' . date('Y') . ' Jurusan Teknik Sepeda Motor — SMKS Islam 1 Kota Blitar. Hak cipta dilindungi.', 'group' => 'umum'],
            'google_maps_url' => ['value' => 'https://www.google.com/maps?q=Blitar&z=14&output=embed', 'group' => 'kontak'],

            'seo_title' => ['value' => 'Jurusan Teknik Sepeda Motor SMKS Islam 1 Blitar', 'group' => 'seo'],
            'seo_description' => ['value' => 'Belajar praktik servis sepeda motor di SMKS Islam 1 Blitar: bengkel lengkap, instruktur berpengalaman, lulusan terserap industri.', 'group' => 'seo'],
            'seo_keywords' => ['value' => 'smk teknik sepeda motor blitar, jurusan tsm, smk islam 1 blitar, bengkel sekolah', 'group' => 'seo'],
            'og_image' => ['value' => null, 'group' => 'seo'],

            'seo_publish_min_score' => ['value' => 50, 'group' => 'seo'],
            'seo_warn_threshold' => ['value' => 60, 'group' => 'seo'],
            'comment_auto_approve' => ['value' => true, 'group' => 'komentar'],
            'comment_rate_limit' => ['value' => 3, 'group' => 'komentar'],
            'comment_max_links' => ['value' => 2, 'group' => 'komentar'],
            'banned_words' => ['value' => ['spam', 'casino', 'judi', 'porn', 'viagra'], 'group' => 'komentar'],
        ];

        foreach ($settings as $key => $cfg) {
            Setting::updateOrCreate(['key' => $key], $cfg);
        }

        // ---------------- sections (landing) ----------------
        $sections = [
            ['key' => 'hero', 'title' => 'Kamu suka mesin? di TSM kamu belajar servis sepeda motor sampai mahir', 'subtitle' => 'Praktik langsung di bengkel sekolah dengan alat standal bengkel modern, dipandu instruktur berpengalaman.', 'sort' => 1, 'extra' => ['headline' => 'Belajar servis sepeda motor sampai mahir', 'cta' => 'Daftar sekarang', 'cta_secondary' => 'Lihat kompetensi']],
            ['key' => 'statistik', 'title' => 'TSM dalam angka', 'subtitle' => 'Angka nyata, bukan janji.', 'sort' => 2, 'extra' => ['stats' => [['label' => 'Siswa aktif', 'value' => '320+'], ['label' => 'Alumni terserap industri', 'value' => '92%'], ['label' => 'Mitra industri', 'value' => '18'], ['label' => 'Prestasi diraih', 'value' => '47']]]],
            ['key' => 'kompetensi', 'title' => 'Lima keahlian yang kamu kuasai', 'subtitle' => 'Setiap keahlian dilatih di bengkel, bukan hanya di papan tulis.', 'sort' => 3],
            ['key' => 'program', 'title' => 'Program unggulan', 'subtitle' => 'Belajar memakai standar bengkel sungguhan.', 'sort' => 4],
            ['key' => 'sarana', 'title' => 'Sarana bengkel', 'subtitle' => 'Alat bengkel lengkap untuk praktik tiap kompetensi.', 'sort' => 5],
            ['key' => 'tahap', 'title' => 'Alur belajar kamu', 'subtitle' => 'Dari dasar sampai mahir dalam tiga tahun.', 'sort' => 6],
            ['key' => 'prestasi', 'title' => 'Prestasi siswa', 'subtitle' => 'Karya dan juara siswa TSM.', 'sort' => 7],
            ['key' => 'berita', 'title' => 'Berita terbaru', 'subtitle' => 'Kegiatan dan informasi terbaru jurusan.', 'sort' => 8],
            ['key' => 'umpan_balik', 'title' => 'Kata alumni', 'subtitle' => 'Cerita alumni setelah lulus dari TSM.', 'sort' => 9],
            ['key' => 'karier', 'title' => 'Prospek karier', 'subtitle' => 'Lulusan TSM dibutuhkan di mana saja.', 'sort' => 10],
            ['key' => 'kontak', 'title' => 'Kunjungi kami', 'subtitle' => 'Mendaftar atau bertanya? hubungi kami.', 'sort' => 11],
            ['key' => 'cta', 'title' => 'Daftar sekarang, mulai praktik semester ini', 'subtitle' => 'Kamu akan belajar di bengkel sejak hari pertama.', 'sort' => 12],
        ];

        foreach ($sections as $sec) {
            Section::updateOrCreate(['key' => $sec['key']], array_merge($sec, ['is_visible' => true]));
        }

        // ---------------- kategori ----------------
        $cats = ['Berita', 'Pengumuman', 'Pengetahuan', 'Kegiatan', 'Prestasi', 'Tips Perawatan'];
        foreach ($cats as $i => $name) {
            Category::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'sort' => $i]
            );
        }

        // ---------------- berita contoh ----------------
        $prestasiCat = Category::where('slug', 'prestasi')->first();
        $beritaCat = Category::where('slug', 'berita')->first();
        $tipsCat = Category::where('slug', 'tips-perawatan')->first();

        $posts = [
            [
                'title' => 'Siswa TSM Juara 2 LKS Tingkat Kota Blitar',
                'slug' => 'siswa-tsm-juara-2-lks-tingkat-kota-blitar',
                'excerpt' => 'Tim TSM SMKS Islam 1 Blitar berhasil meraih juara 2 dalam Lomba Kompetensi Siswa tingkat Kota Blitar bidang sepeda motor.',
                'body' => $this->bodySample('Tim kami berlatih selama dua bulan di bengkel sekolah. Mereka harus menyelesaikan diagnosis kerusakan mesin, perawatan transmisi, dan pengetesan sistem kelistrikan dalam batas waktu yang ketat. Hasilnya, juara 2 tingkat Kota Blitar.'),
                'category_id' => $prestasiCat?->id,
                'focus_keyword' => 'juara LKS sepeda motor blitar',
                'seo_title' => 'Siswa TSM Juara 2 LKS Tingkat Kota Blitar',
                'seo_description' => 'Tim TSM SMKS Islam 1 Blitar meraih juara 2 LKS bidang sepeda motor tingkat Kota Blitar setelah dua bulan berlatih di bengkel sekolah.',
                'status' => PostStatus::Published,
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Pendaftaran Peserta Didik Baru Jurusan TSM Dibuka',
                'slug' => 'pendaftaran-peserta-didik-baru-jurusan-tsm-dibuka',
                'excerpt' => 'Pendaftaran PPDB jurusan Teknik Sepeda Motor SMKS Islam 1 Blitar dibuka. Kuota terbatas, daftar sekarang.',
                'body' => $this->bodySample('Pendaftaran dibuka untuk calon siswa kelas X. Jurusan TSM menyiapkan bengkel lengkap, instruktur berpengalaman, dan kesempatan magang di bengkel mitra. Hubungi kami untuk informasi pendaftaran.'),
                'category_id' => $beritaCat?->id,
                'focus_keyword' => 'pendaftaran smk teknik sepeda motor blitar',
                'seo_title' => 'Pendaftaran Peserta Didik Baru Jurusan TSM Dibuka',
                'seo_description' => 'PPDB jurusan Teknik Sepeda Motor SMKS Islam 1 Blitar dibuka dengan kuota terbatas. Daftar sekarang sebelum penuh.',
                'status' => PostStatus::Published,
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Tips Merawat Mesin Sepeda Motor Injeksi',
                'slug' => 'tips-merawat-mesin-sepeda-motor-injeksi',
                'excerpt' => 'Lima hal sederhana yang menjaga mesin injeksi kamu tetap awet dan irit.',
                'body' => $this->bodySample('Motor injeksi butuh perawatan berbeda dari motor karburator. Ganti oli tepat waktu, bersihkan filter udara, dan perhatikan indikator check engine. Praktik ini juga kami ajarkan di kelas X.'),
                'category_id' => $tipsCat?->id,
                'focus_keyword' => 'merawat motor injeksi',
                'seo_title' => 'Tips Merawat Mesin Sepeda Motor Injeksi',
                'seo_description' => 'Lima tips sederhana merawat mesin sepeda motor injeksi agar tetap awet dan irit, sesuai materi praktik TSM.',
                'status' => PostStatus::Published,
                'published_at' => now()->subDays(8),
            ],
        ];

        foreach ($posts as $data) {
            Post::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'author_id' => $editor->id,
                    'allow_comments' => true,
                    'seo_score' => 72,
                ])
            );
        }

        // komentar contoh
        $firstPost = Post::first();
        if ($firstPost !== null) {
            \App\Models\Comment::updateOrCreate(
                ['post_id' => $firstPost->id, 'name' => 'Pengunjung'],
                [
                    'email' => 'pengunjung@example.com',
                    'body' => 'Selamat atas prestasinya! Semoga makin banyak juara lagi.',
                    'status' => CommentStatus::Approved,
                    'visitor_hash' => 'seeder-hash',
                    'is_admin_reply' => false,
                ]
            );
            $firstPost->update([
                'comments_count' => 1,
                'views_count' => 312,
                'likes_count' => 36,
                'unlikes_count' => 6,
                'like_percent' => 85.7,
                'engagement_percent' => 13.5,
                'stars' => 4.1,
            ]);
        }
    }

    private function bodySample(string $first): string
    {
        return '<p>' . $first . '</p><h2>Detail kegiatan</h2><p>Kegiatan dilaksanakan di bengkel sekolah dengan pengawasan instruktur. Siswa praktik memakai alat standar bengkel modern yang tersedia lengkap di jurusan.</p><h3>Langkah praktik</h3><p>Mulai dari pemeriksaan kondisi mesin, diagnosis kerusakan, sampai uji jalan. Setiap langkah didokumentasikan di lembar kerja seperti bekerja di bengkel sungguhan.</p>';
    }
}
