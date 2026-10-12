# CMS TSM Blitar

Website sekolah untuk **SMKS Islam 1 Blitar** — jurusan Teknik Sepeda Motor.
Dibangun dengan **Laravel 13 (API) + Vue 3 SPA (2 entry)**, mengikuti spesifikasi
`CMS-TSM-BLITAR.md` dan design tokens `DESIGN.md`.

## Stack

- **Backend**: Laravel 13, Sanctum (cookie auth), MySQL/MariaDB `cms_tsm`, PHP 8.3
- **Frontend**: Vue 3 `<script setup lang="ts">`, Vite 2-entry (publik + admin), TanStack Query, Pinia, Chart.js
- **CSS**: Design tokens (`resources/css/tokens.css`) — primary `#0D47A1`, bg `#E3F2FD`, ink `#0B1F3A`
- **Keamanan**: HTMLPurifier, SecurityMonitor deteksi serangan, rate limiter, hash pengunjung

## Cara menjalankan (lokal)

1. Nyalakan MySQL (XAMPP Control Panel → MySQL Start).
2. Dobel-klik `jalankan-cms.bat`.
3. Buka `http://127.0.0.1:8000` (publik) atau `http://127.0.0.1:8000/admin` (admin).
4. Dari HP pada WiFi yang sama: `http://192.168.1.6:8000`.

> Jika server belum jalan, jalankan manual:
> `C:\php83\php.exe artisan serve --host=0.0.0.0 --port=8000`

## Login admin

- Email: `admin@tsm-blitar.sch.id`
- Sandi: `admin12345`

Role lain (seeder): `editor@tsm-blitar.sch.id` / `editor12345` (editor),
`penulis@tsm-blitar.sch.id` / `penulis12345` (penulis).

## Fitur

### Publik
- Landing dinamis (hero, window card bengkel, statistik, kompetensi, program, sarana, prestasi, mitra, FAQ, CTA WhatsApp, peta)
- Berita + detail (komentar, like, share WhatsApp/Facebook/X/Telegram/salin, berita terkait)
- Prestasi, Galeri, Tentang (staff), Kontak (form + peta)
- SEO server-side: meta tag, JSON-LD, canonical, sitemap.xml, robots.txt, RSS feed

### Admin
- Dashboard (statistik kunjungan 7d/30d, grafik Chart.js, berita terbaru, komentar pending)
- Berita: CRUD + draf/review/scheduled/publish/archive + revisi + analisa SEO (skor + saran)
- Kategori, Komentar (moderasi, balas, bulk), Konten situs (sections + 9 item lists + urutan)
- Media library (unggah, edit alt/caption, hapus)
- Pesan masuk, Umpan balik alumni
- Pengaturan (umum/seo/komentar), Pengguna (CRUD + role + aktif)
- **Log aktivitas** — dengan tab "Serangan terdeteksi" (insight keamanan)

### Keamanan
- **SecurityMonitor**: deteksi SQLi, XSS, path traversal, LFI, command injection, exploit Laravel populer; auto-ban IP setelah ambang batas
- **ActivityLogger**: semua aksi admin tercatat di `activity_logs` (siapa, kapan, apa, dari mana)
- Throttle login 5 percobaan/menit, percobaan login tersimpan di `cms_login_attempts`
- Sanitasi HTML berbasis HTMLPurifier (cek `app/Services/HtmlSanitizer.php`)
- Hash pengunjung — alamat IP tidak disimpan mentah, hanya hash (privacy by design)
- Header keamanan (`SecurityHeaders` middleware)

## Struktur

```
app/
├── Http/Controllers/Api/V1/
│   ├── Admin/      # 20 controller admin (berita, kategori, konten, media, ...)
│   └── Public/     # endpoint publik (landing, posts, comments, contact, track)
├── Models/         # 28 model (tabel cms_* eksplisit)
├── Policies/       # PostPolicy, CommentPolicy, UserPolicy, CmsContentPolicy
├── Services/       # SecurityMonitor, ActivityLogger, HtmlSanitizer, SeoAnalyzer, VisitorHasher
└── Enums/          # Role, PostStatus, CommentStatus, MessageType, ShareChannel
resources/js/
├── public/         # SPA publik (landing, berita, prestasi, galeri, tentang, kontak)
└── admin/          # SPA admin (dashboard, berita, kategori, komentar, konten, media, ...)
routes/
├── api.php         # API v1 (publik + admin + auth)
└── web.php         # SEO shell + sitemap + robots + RSS
```

## Verifikasi

Semua endpoint admin + publik telah diuji dengan login asli (lihat log):
12 endpoint admin → 200, 5 endpoint publik → 200, deteksi serangan → 400 + tercatat,
login throttle aktif, build Vite exit 0.

## Repo

`https://github.com/fikrudzulfahmi/cms-tsm.git`
