# CMS Jurusan Teknik Sepeda Motor (TSM) — SMKS Islam 1 Kota Blitar

**Revisi 2: Laravel 13, arsitektur API + SPA.**
Dokumen konsep dan struktur untuk dikerjakan oleh agent (Hermes).
Alamat: Jl. Musi No. 06 Kota Blitar.
Acuan visual wajib: `DESIGN.md` (dan `index.html` jika ada). Jangan menambah warna atau gaya di luar sistem itu.

---

## 1. Ringkasan

Satu aplikasi dengan tiga bagian yang dipisahkan tegas:

1. **Backend API (Laravel 13).** Satu-satunya sumber data dan logika. Tidak merender halaman, kecuali "cangkang SEO" untuk SPA (lihat 5.4).
2. **SPA Publik (Vue 3).** Landing page, berita, interaksi pengunjung.
3. **SPA Admin (Vue 3).** Panel CMS, dashboard, editor berita, laporan.

Kedua SPA berbicara ke API yang sama lewat `/api/v1`. Tidak ada logika bisnis di frontend selain tampilan dan validasi awal.

Prinsip utama:

- **Semua teks dan foto bisa diubah dari admin.** Tidak ada teks hardcode di komponen SPA. Semua datang dari API.
- **API-first.** Setiap fitur dibuat sebagai endpoint yang terdokumentasi dan teruji lebih dulu, baru dipakai SPA.
- **SEO tidak boleh dikorbankan oleh SPA.** Berita dan landing page tetap terbaca crawler dan pratinjau WhatsApp/Facebook (lihat 5.4).
- **Satu repositori, satu deploy, satu domain.** Mudah dipasang di VPS atau hosting sekolah dan memungkinkan autentikasi cookie yang aman.
- **Aman by default.** Input pengunjung dianggap tidak tepercaya.
- **Satu keluarga visual.** Kedua SPA memakai token `DESIGN.md`.

---

## 2. Teknologi

### 2.1 Backend

| Komponen | Pilihan | Catatan |
|---|---|---|
| Framework | **Laravel 13** | Versi mayor terbaru (rilis 17 Maret 2026). |
| Bahasa | **PHP 8.3 minimum, disarankan 8.4** | Laravel 13 mewajibkan PHP 8.3+. **Cek dulu hosting mendukung PHP 8.3+.** Bila tidak, gunakan VPS. |
| Database | MySQL 8 / MariaDB 10.11+ | Alternatif: PostgreSQL. |
| Auth SPA | **Laravel Sanctum (mode SPA, cookie + CSRF)** | Tanpa token di localStorage. |
| Antrean | Queue driver `database` (atau Redis bila ada) | Email, kompresi gambar, laporan PDF. |
| Cache | `database`/`file` (Redis bila ada) | Cache respons landing page dan statistik. |
| Penjadwal | Laravel Scheduler (1 cron per menit) | Jadwal terbit, rekap harian, laporan bulanan, backup. |
| Dokumentasi API | **Scramble** (OpenAPI otomatis dari kode) | UI dokumentasi di `/docs/api` (hanya admin/lokal). |
| Sanitasi HTML | `mews/purifier` | Isi berita dan komentar. |
| Gambar | `intervention/image` (driver GD/Imagick) | Resize, WebP, varian ukuran. |
| PDF / Excel | `barryvdh/laravel-dompdf`, `maatwebsite/excel` | Ekspor laporan. |
| Backup | `spatie/laravel-backup` | Database + media. |
| Peran | Policy + Gate bawaan Laravel (atau `spatie/laravel-permission`) | |
| Kualitas | **Pest**, Laravel Pint, Larastan | Pengujian dan gaya kode. |

### 2.2 Frontend (dua SPA, satu codebase)

| Komponen | Pilihan |
|---|---|
| Framework | **Vue 3** (Composition API, `<script setup>`) + **TypeScript** |
| Build | **Vite** lewat plugin `laravel-vite-plugin` |
| Routing | Vue Router 4 (dua aplikasi: `public` dan `admin`) |
| State | Pinia (sesi, UI) |
| Data fetching | **TanStack Query (Vue Query)** + `axios` (cache, retry, invalidasi) |
| Form dan validasi | VeeValidate + Zod |
| Meta/SEO klien | `@unhead/vue` |
| Editor teks | **TipTap** (Vue) |
| Grafik | Chart.js (`vue-chartjs`) |
| Utilitas | VueUse, `dayjs` |
| Gaya | **CSS murni berbasis token `DESIGN.md`** (`tokens.css`), tanpa framework UI berwarna. Boleh pakai library headless (Radix Vue / Headless UI) untuk dialog dan dropdown agar aksesibel. |
| Font | Plus Jakarta Sans, JetBrains Mono (self-host, bukan dari CDN) |

Catatan: SPA publik dan admin dipisah menjadi dua *entry point* Vite (`public.ts`, `admin.ts`) agar pengunjung tidak mengunduh kode admin.

---

## 3. Arsitektur

```
Browser (pengunjung/admin)
   │  HTML shell + JS (Vite build)
   ▼
┌─────────────────────────── Satu domain: tsm.example.sch.id ───────────────────────────┐
│  /               → shell SPA Publik   (Blade tipis + meta SEO dari server)            │
│  /admin/*        → shell SPA Admin    (Blade tipis, noindex)                          │
│  /api/v1/*       → REST API JSON      (Laravel 13, Sanctum, throttle)                 │
│  /sitemap.xml /robots.txt /feed.xml   → dirender server                               │
│  /storage/*      → media (WebP + varian)                                              │
└────────────────────────────────────────────────────────────────────────────────────────┘
   ▼
MySQL · Queue · Cache · Storage · Scheduler
```

Lapisan di backend:

```
Route → Middleware (throttle, auth, role) → FormRequest (validasi)
      → Controller tipis → Action/Service (logika) → Model/Eloquent
      → API Resource (bentuk JSON) → Respons
```

Aturan:

- **Controller tipis.** Logika di `Actions/` dan `Services/`.
- **Respons selalu lewat API Resource** supaya bentuk JSON stabil dan tidak membocorkan kolom internal (email komentator, hash pengunjung).
- **Policy** untuk setiap resource admin.
- **Versi API** `/api/v1`. Perubahan yang memecah klien masuk `/v2`.

---

## 4. Peran pengguna

| Role | Hak akses |
|---|---|
| **Super Admin** | Semua, termasuk pengaturan, pengguna, backup, log aktivitas. |
| **Editor** | Kelola berita, kategori, tag, konten situs, media, prestasi, galeri. Moderasi komentar. |
| **Penulis** | Tulis berita sendiri (draf atau menunggu review). Tidak bisa publikasi langsung. |
| **Pengunjung** | Tanpa akun. Baca, like/unlike, komentar, kirim umpan balik/pesan. |

Login admin: email + kata sandi (argon2/bcrypt), rate limit, opsi 2FA (TOTP) untuk Super Admin. Izin dicek **di API** (Policy), bukan hanya disembunyikan di UI.

---

## 5. SPA Publik

### 5.1 Struktur halaman (landing page)

Mengikuti `DESIGN.md` bagian 9, diperluas. **Setiap section punya saklar tampil/sembunyi dan urutan yang diatur dari admin.** Seluruh data landing diambil dengan **satu panggilan** `GET /api/v1/site/landing`.

```
[Header sticky: logo | menu | tombol "Daftar sekarang"]
1. Hero / Headline        judul + CTA kiri | foto Kepala Konsentrasi Keahlian di jendela kanan
2. Strip statistik        4 angka (siswa, alumni terserap, mitra industri, prestasi)
3. Kompetensi keahlian    grid 3 kolom, kartu pertama berlatar primary
4. Program unggulan       kartu program (praktik bengkel, teaching factory, sertifikasi)
5. Sarana                 teks kiri | jendela tabel spesifikasi/daftar alat kanan + galeri
6. Alur belajar           3 kartu kelas X, XI, XII
7. Prestasi               kartu/daftar prestasi (tahun, tingkat, lomba)
8. Berita terkini         3 sampai 6 berita terbaru + filter kategori
9. Umpan balik lulusan    kutipan alumni/DUDI + formulir kirim umpan balik
10. Karier                chip prospek karier
11. Kontak dan lokasi     alamat, peta, WhatsApp, jam layanan
12. CTA pendaftaran       blok primary
[Footer]
```

Hero: foto Kepala Konsentrasi Keahlian dibungkus komponen jendela (bar atas + radius 24px). Nama, gelar, dan sapaan bisa diubah dari admin.
Bahasa: Indonesia, sapaan "kamu", judul menjelaskan isi, tombol memakai kata kerja spesifik.

### 5.2 Rute SPA publik (Vue Router, mode history)

```
/                       Landing page
/berita                 Daftar (?kategori=&q=&page=)
/berita/kategori/:slug  Daftar per kategori
/berita/:slug           Detail + like/unlike + bagikan + komentar
/prestasi               Daftar prestasi
/galeri                 Galeri
/tentang                Tentang jurusan, profil guru
/kontak                 Kontak, peta, formulir pesan/pendaftaran
/:pathMatch(.*)*        Halaman 404
```

### 5.3 Perilaku SPA

- Transisi antarhalaman tanpa muat ulang, **kerangka (skeleton) saat memuat**, tanpa animasi masuk berlebihan (`DESIGN.md` bagian 6).
- Pengaturan fokus dan judul halaman otomatis tiap pindah rute (aksesibilitas).
- Cache TanStack Query: landing 5 menit, daftar berita 1 menit, detail berita 5 menit.
- Pelacakan kunjungan: tiap pindah rute memanggil `POST /api/v1/track` memakai `navigator.sendBeacon`.
- Like, unlike, dan komentar memakai *optimistic update* lalu sinkron dengan respons API.
- Kesalahan jaringan: tampilan ramah dengan tombol "Coba lagi".

### 5.4 SEO untuk SPA (wajib, karena fitur analisis SEO ketat)

SPA murni buruk untuk SEO dan pratinjau tautan (crawler WhatsApp/Facebook tidak menjalankan JavaScript). Solusinya **cangkang SEO dari server** tanpa perlu Node SSR:

1. Rute catch-all Laravel melayani `resources/views/spa/public.blade.php` untuk URL publik.
2. Untuk URL yang dikenal (`/`, `/berita`, `/berita/{slug}`, kategori, prestasi, dll.), controller `SeoShellController` mengisi di `<head>`: `<title>`, meta description, canonical, Open Graph, Twitter Card, **JSON-LD** (`NewsArticle`, `BreadcrumbList`, `Organization`, `FAQPage`).
3. Di dalam `<div id="app">`, server menulis **konten semantik ringkas** (judul H1, ringkasan, isi berita bersih) sebagai fallback untuk crawler dan pengguna tanpa JS. Vue menggantikan isi itu saat dimuat (tanpa hydration).
4. Slug tidak ditemukan mengembalikan status HTTP **404** yang sebenarnya, bukan 200.
5. Data awal disisipkan sebagai `window.__INITIAL__` (JSON) agar render pertama SPA tidak menunggu panggilan API.
6. `sitemap.xml`, `robots.txt`, `feed.xml` dirender server dan di-cache.
7. Shell admin mengirim `X-Robots-Tag: noindex, nofollow`.

Jalur peningkatan bila nanti ada VPS dengan Node: pindahkan SPA publik ke Nuxt (SSR) tanpa mengubah API sama sekali. Karena itu API tidak boleh bergantung pada Blade.

---

## 6. SPA Admin

### 6.1 Menu dan rute (`/admin/*`)

```
/admin/login
/admin                          Beranda (dashboard)
/admin/berita                   Semua berita
/admin/berita/baru              Tulis berita
/admin/berita/:id/sunting
/admin/berita/kategori
/admin/berita/tag
/admin/komentar                 Moderasi
/admin/konten/hero              Hero / headline
/admin/konten/statistik
/admin/konten/kompetensi
/admin/konten/program
/admin/konten/sarana
/admin/konten/tahap             Alur belajar
/admin/konten/prestasi
/admin/konten/umpan-balik       Umpan balik lulusan (moderasi)
/admin/konten/karier
/admin/konten/mitra
/admin/konten/galeri
/admin/konten/staf
/admin/konten/faq
/admin/konten/kontak
/admin/pesan                    Pesan masuk / pendaftaran
/admin/media                    Pustaka media
/admin/laporan
/admin/pengaturan/umum | seo | menu | backup
/admin/pengguna
/admin/log-aktivitas
```

Menu yang tampil mengikuti role pengguna (dari `GET /api/v1/admin/me`), tetapi penegakan izin tetap di API.

### 6.2 Beranda (dashboard)

Kartu ringkasan (periode: 7 hari, 30 hari, 12 bulan), semua dari `GET /api/v1/admin/dashboard`:

- Total kunjungan, pengunjung unik, halaman dilihat, grafik kunjungan harian
- Jumlah berita (terbit, draf, terjadwal), prestasi, kompetensi, foto galeri
- Berita terpopuler (tampilan, like, komentar, rating)
- Komentar dan umpan balik menunggu moderasi
- Sumber trafik (langsung, pencarian, media sosial, rujukan) dan perangkat
- Peringatan: berita dengan skor SEO rendah, backup terakhir, antrean gagal

### 6.3 Semua teks dan foto bisa diubah

- Tabel `settings` (key-value) untuk teks global: nama sekolah, tagline, alamat, kontak, teks tombol, footer.
- Tabel `sections` untuk tiap blok landing page: judul, subjudul, isi, gambar, urutan, tampil/tidak.
- Tabel khusus untuk daftar item (kompetensi, program, sarana, prestasi, dst.) dengan urutan yang bisa digeser (drag and drop, lalu `PATCH .../reorder`).
- Semua gambar dipilih dari **Pustaka Media** (unggah, crop, ganti, teks alt wajib).
- Tombol **Pratinjau**: admin membuka landing/berita dengan token pratinjau sekali pakai sebelum menyimpan perubahan besar.
- Setiap perubahan konten membersihkan cache landing (event model → `Cache::forget`).

---

## 7. Modul Berita (inti)

### 7.1 Editor

- TipTap: H2, H3, tebal, miring, garis bawah, daftar, kutipan, tautan, tabel, gambar (alt dan keterangan), embed YouTube, pemisah.
- Simpan otomatis draf tiap 30 detik (`PATCH` ringan, tanpa membuat revisi baru).
- Status: `draf`, `menunggu_review`, `terjadwal`, `terbit`, `arsip`.
- Penjadwalan terbit lewat Scheduler.
- Riwayat revisi dan tombol pulihkan.
- **Isi disanitasi di server** (HTMLPurifier, whitelist tag) sebelum disimpan. Klien tidak dipercaya.

Field berita: judul, slug, ringkasan, isi, gambar utama (+ alt), kategori, tag, penulis, tanggal terbit, status, opsi komentar, dan **blok SEO** (7.2).

### 7.2 Analisis SEO yang ketat

Panel SEO di samping editor, **skor 0 sampai 100** dan daftar cek (ikon + teks, tidak hanya warna; status sukses/error boleh hijau/merah sesuai `DESIGN.md`).

**Satu sumber kebenaran: server.** Kelas `SeoAnalyzer` (PHP) memuat semua aturan. SPA memanggil `POST /api/v1/admin/seo/analyze` dengan debounce 600 ms saat mengetik. Hasilnya disimpan ke `posts.seo_score` saat berita disimpan. Aturan yang sama dipakai laporan, sehingga skor di editor dan di dashboard tidak pernah berbeda.

Input SEO: **kata kunci fokus** (wajib), judul SEO, meta description, slug, gambar OG.

| Kelompok | Aturan | Batas |
|---|---|---|
| **Kata kunci** | Ada di judul SEO (sebaiknya di awal) | wajib |
| | Ada di paragraf pertama | wajib |
| | Ada di minimal satu subjudul H2/H3 | wajib |
| | Ada di slug | wajib |
| | Ada di meta description | wajib |
| | Ada di alt gambar utama | wajib |
| | Kepadatan kata kunci 0,5% sampai 2,5% | di luar rentang = peringatan |
| | Kata kunci fokus belum dipakai berita lain | cegah kanibalisasi |
| **Judul** | Judul SEO 30 sampai 60 karakter | di luar = peringatan |
| | Judul SEO unik di seluruh situs | wajib |
| **Meta description** | 120 sampai 160 karakter | di luar = peringatan |
| **Slug** | Huruf kecil, tanda hubung, maks 75 karakter | wajib |
| **Konten** | Minimal 300 kata (pengumuman pendek boleh dikecualikan per kategori) | wajib |
| | Satu H1 saja (judul), subjudul berurutan H2 lalu H3 | wajib |
| | Subjudul minimal tiap 300 kata | peringatan |
| | Paragraf maksimal 150 kata | peringatan |
| **Keterbacaan** | Kalimat panjang (>25 kata) maksimal 25% | peringatan |
| | Kata transisi minimal 20% kalimat | saran |
| | Kalimat pasif maksimal 15% (heuristik awalan di-, ter-, ke-an) | saran |
| **Tautan** | Minimal 1 tautan internal | wajib |
| | Minimal 1 tautan keluar (`rel="noopener"`) | saran |
| **Gambar** | Ada gambar utama, semua gambar punya alt | wajib |
| | Gambar utama < 300 KB setelah kompresi | peringatan |
| **Media sosial** | Judul OG, deskripsi OG, gambar OG 1200x630 | wajib |
| **Teknis** | Canonical, JSON-LD `NewsArticle`, breadcrumb | otomatis |

Perilaku:

- Skor < 60: tombol **Terbitkan** memunculkan peringatan. Super Admin bisa mengatur pemblokiran total (default: blokir bila skor < 50). **Aturan ini ditegakkan di API** (`POST /admin/posts/{id}/publish` menolak dengan 422), bukan hanya di UI.
- Pratinjau tampilan hasil Google dan kartu bagikan (WhatsApp/Facebook).
- Tiap cek berisi penjelasan singkat cara memperbaiki.
- Semua ambang batas tersimpan di pengaturan.

### 7.3 Interaksi pengunjung

**Identitas pengunjung anonim.** API menerbitkan cookie `vid` (acak, `HttpOnly`, `Secure`, `SameSite=Lax`, berlaku 1 tahun). Server menyimpan `visitor_hash = HMAC(vid + UA, rahasia)`; IP mentah tidak disimpan.

**Like / Unlike**

- Satu pengunjung satu suara per berita (unik `post_id + visitor_hash`).
- Endpoint **idempoten**: `PUT /posts/{slug}/reaction` dengan `{ "value": 1 | -1 | 0 }` (0 = tarik suara). Dapat mengganti pilihan kapan saja.
- Respons mengembalikan hitungan terbaru dan suara pengunjung saat ini.

**Rating** (perbandingan like, unlike, dan pengunjung)

- `rating_suka` = like / (like + unlike) x 100%.
- `tingkat_keterlibatan` = (like + unlike) / pengunjung unik berita x 100%.
- `skor_bintang` = 5 x batas bawah interval Wilson dari like/(like+unlike), agar suara sedikit tidak menyesatkan.
- Tampilan: "86% suka dari 42 suara". Suara < 5 diberi label "belum cukup suara".
- Dihitung oleh `RatingCalculator`, disimpan di kolom `posts` dan diperbarui lewat job saat ada reaksi.

**Bagikan**

- Tombol WhatsApp, Facebook, X, Telegram, Salin tautan, dan Web Share API di HP.
- `POST /posts/{slug}/share` mencatat klik per kanal. Tautan memakai parameter UTM.

**Komentar**

- Formulir: nama, email (tidak ditampilkan), isi. Tanpa login.
- Balasan bersarang (maks 3 tingkat), like komentar, laporkan komentar.
- Status: `menunggu`, `disetujui`, `ditolak`, `spam`. Opsi auto-setujui untuk pengomentar yang pernah disetujui.
- Anti-spam: honeypot, rate limit (3 komentar per 5 menit per pengunjung), batas tautan, daftar kata terlarang, opsi Cloudflare Turnstile.
- Admin bisa setujui, tolak, tandai spam, hapus, balas sebagai "Admin" (lencana), dan menutup komentar per berita.
- Notifikasi email (lewat queue) ke admin untuk komentar baru dan ke pengomentar saat dibalas (opsional).
- Teks komentar disanitasi (tanpa HTML), email tidak pernah muncul di respons publik.

### 7.4 Kategori

Awal: **Berita, Pengumuman, Pengetahuan, Kegiatan, Prestasi, Tips Perawatan**. Bisa ditambah dan diurutkan dari admin. Tiap kategori punya slug, deskripsi, dan meta SEO sendiri.

---

## 8. Laporan periodik dan analisis

### 8.1 Data yang dikumpulkan

- **Kunjungan:** per halaman, tanggal, pengunjung unik (hash), sumber rujukan, perangkat, browser, negara/kota (opsional, GeoIP lokal). Bot difilter.
- **Interaksi berita:** tampilan, like, unlike, komentar, klik bagikan per kanal.
- **Produksi konten:** jumlah berita dibuat/terbit per penulis dan kategori.
- **Formulir:** pendaftaran/pesan masuk, umpan balik lulusan.

Pencatatan lewat `POST /api/v1/track` (dari SPA) dan job antrean. Tabel `daily_stats` direkap tiap malam agar dashboard cepat. Tanpa IP mentah dan tanpa pelacak pihak ketiga; beri keterangan singkat di footer.

### 8.2 Jenis laporan

| Laporan | Isi |
|---|---|
| **Ringkasan bulanan/kuartalan/tahunan** | Kunjungan, pengunjung unik, tren dibanding periode sebelumnya (naik/turun %), halaman terpopuler, sumber trafik, perangkat. |
| **Produksi berita** | Berita terbit per bulan, per kategori, per penulis; rata-rata skor SEO; draf yang tertunda. |
| **Performa berita** | Peringkat berita (tampilan, rating, komentar, bagikan), kategori paling diminati. |
| **Interaksi** | Komentar masuk/disetujui/ditolak, rasio spam, aktivitas like. |
| **Pendaftaran dan umpan balik** | Jumlah pendaftar/pesan, umpan balik baru. |
| **Analisis dan saran otomatis** | Kalimat ringkas dari `InsightGenerator`, mis. "Kategori Pengumuman paling banyak dibaca. Berita dengan skor SEO >80 rata-rata dibaca 2x lebih banyak." |

### 8.3 Fitur

- Pilih rentang tanggal dan pembanding periode.
- Ekspor **PDF** (berkop jurusan) dan **Excel/CSV**, dibuat lewat queue lalu diunduh dari `GET /admin/reports/{id}/download` (file ditandatangani, kedaluwarsa).
- **Laporan otomatis bulanan** dikirim ke email Super Admin (Scheduler).
- Grafik: garis (tren), batang (kategori), donat (sumber trafik), palet biru-baja.

---

## 9. Spesifikasi API

### 9.1 Konvensi

- Basis: `/api/v1`. Format JSON, UTF-8, tanggal ISO 8601 (zona Asia/Jakarta diatur di klien; server menyimpan UTC).
- Penamaan resource: bentuk jamak bahasa Inggris pada URL API (`posts`, `categories`, `comments`); label tampilan bahasa Indonesia ada di SPA.
- **Respons sukses:**
  ```json
  { "data": { ... }, "meta": { "page": 1, "per_page": 12, "total": 87 }, "links": { "next": "..." } }
  ```
- **Respons galat** (status HTTP benar: 401, 403, 404, 409, 422, 429, 500):
  ```json
  { "message": "Data tidak valid.", "errors": { "title": ["Judul wajib diisi."] } }
  ```
- Pesan galat berbahasa Indonesia (`lang/id`).
- Paginasi `?page=&per_page=` (maks 50). Daftar admin memakai `?sort=-created_at&filter[status]=terbit&q=`.
- Cache publik: header `Cache-Control` + `ETag` untuk endpoint publik yang aman di-cache (`site/landing`, daftar berita).
- Pembatasan laju (rate limit) bernama: `public` (120/menit), `interact` (reaksi, komentar, formulir: 20/menit), `auth` (login: 5/menit), `admin` (300/menit).
- Pembuatan/ubah data yang dapat dicoba ulang menerima header `Idempotency-Key` (opsional untuk unggah dan publikasi).

### 9.2 Autentikasi (Sanctum SPA)

```
GET  /sanctum/csrf-cookie           ambil cookie CSRF
POST /api/v1/auth/login             {email, password, (otp)}
POST /api/v1/auth/logout
GET  /api/v1/admin/me               profil + role + izin
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
```

Konfigurasi: `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS` sesuai domain; cookie `HttpOnly`, `Secure`, `SameSite=Lax`. Karena SPA dan API satu domain, tidak butuh CORS khusus. Tidak ada token yang disimpan di `localStorage`.

### 9.3 Endpoint publik (tanpa login)

| Metode | Path | Fungsi |
|---|---|---|
| GET | `/site/landing` | Seluruh data landing page (hero, statistik, kompetensi, program, sarana, tahap, prestasi, karier, mitra, kontak, urutan section, 6 berita terbaru, umpan balik disetujui). |
| GET | `/site/settings` | Identitas situs, menu navigasi, footer, SEO global. |
| GET | `/posts` | Daftar berita terbit (`category`, `tag`, `q`, `page`). |
| GET | `/posts/{slug}` | Detail berita + hitungan reaksi + suara pengunjung saat ini + berita terkait. |
| GET | `/categories` | Daftar kategori + jumlah berita. |
| PUT | `/posts/{slug}/reaction` | Like/unlike/tarik (`value`: 1, -1, 0). |
| POST | `/posts/{slug}/share` | Catat klik bagikan (`channel`). |
| GET | `/posts/{slug}/comments` | Komentar disetujui (bersarang, terpaginasi). |
| POST | `/posts/{slug}/comments` | Kirim komentar/balasan (`parent_id` opsional). |
| PUT | `/comments/{id}/like` | Like komentar (toggle idempoten). |
| POST | `/comments/{id}/report` | Laporkan komentar. |
| GET | `/achievements` | Daftar prestasi (filter tahun/tingkat). |
| GET | `/gallery` | Galeri (album, paginasi). |
| GET | `/staff` `/faqs` `/partners` | Profil guru, FAQ, mitra. |
| POST | `/feedback` | Kirim umpan balik lulusan (status awal `pending`). |
| POST | `/contact` | Pesan/pendaftaran. |
| GET | `/search` | Cari berita, `q`. |
| POST | `/track` | Catat kunjungan (`path`, `post_slug`, `referrer`); selalu 204. |

### 9.4 Endpoint admin (`/admin`, `auth:sanctum` + Policy)

| Area | Endpoint |
|---|---|
| Dashboard | `GET /admin/dashboard?range=` |
| Berita | `apiResource /admin/posts` · `POST /admin/posts/{id}/publish` · `/unpublish` · `/schedule` · `/archive` · `GET /admin/posts/{id}/revisions` · `POST /admin/posts/{id}/revisions/{rev}/restore` · `POST /admin/posts/{id}/preview-token` |
| SEO | `POST /admin/seo/analyze` |
| Kategori, tag | `apiResource /admin/categories`, `/admin/tags` · `PATCH .../reorder` |
| Komentar | `GET /admin/comments?status=` · `PATCH /admin/comments/{id}` (setujui/tolak/spam) · `POST /admin/comments/{id}/reply` · `DELETE ...` · `POST /admin/comments/bulk` |
| Konten situs | `GET/PUT /admin/sections/{key}` (hero, statistik, kontak, dst.) · `PATCH /admin/sections/order` |
| Daftar item | `apiResource /admin/{competencies,programs,facilities,stages,achievements,careers,partners,gallery,staff,faqs}` · `PATCH .../reorder` |
| Umpan balik | `GET /admin/feedback` · `PATCH /admin/feedback/{id}` |
| Pesan | `GET /admin/messages` · `PATCH /admin/messages/{id}` |
| Media | `GET /admin/media` · `POST /admin/media` (multipart) · `PATCH /admin/media/{id}` (alt, keterangan) · `DELETE ...` |
| Laporan | `GET /admin/reports/preview?type=&from=&to=` · `POST /admin/reports` (antre) · `GET /admin/reports` · `GET /admin/reports/{id}/download` |
| Pengaturan | `GET/PUT /admin/settings/{group}` (umum, seo, menu) · `POST /admin/backups` · `GET /admin/backups` |
| Pengguna | `apiResource /admin/users` (Super Admin) |
| Log | `GET /admin/activity-logs` |

### 9.5 Bentuk data contoh

`GET /api/v1/posts/{slug}`:

```json
{
  "data": {
    "id": 12,
    "title": "Siswa TSM Juara 2 LKS Tingkat Kota",
    "slug": "siswa-tsm-juara-2-lks-tingkat-kota",
    "excerpt": "...",
    "body_html": "<p>...</p>",
    "featured_image": { "url": "...", "alt": "...", "width": 1200, "height": 630 },
    "category": { "id": 2, "name": "Prestasi", "slug": "prestasi" },
    "tags": ["lks", "juara"],
    "author": { "name": "Admin TSM", "avatar": null },
    "published_at": "2026-10-12T03:00:00Z",
    "stats": {
      "views": 312, "likes": 36, "unlikes": 6, "comments": 9, "shares": 14,
      "like_percent": 85.7, "engagement_percent": 13.5, "stars": 4.1
    },
    "viewer": { "reaction": 1 },
    "seo": { "title": "...", "description": "...", "canonical": "...", "og_image": "..." }
  }
}
```

Catatan: field milik admin (`seo_score`, `focus_keyword`, status draf, email komentator, hash pengunjung) **tidak pernah** ada di API publik. Gunakan API Resource terpisah untuk publik dan admin.

---

## 10. Skema database (ringkas)

```
users               id, name, email, password, role, avatar, is_active, two_factor_*, last_login_at
settings            id, key, value(json), group
sections            id, key, title, subtitle, body, image_id, sort, is_visible, extra(json)
media               id, path, disk, mime, size, width, height, alt, caption, variants(json), uploaded_by
competencies        id, title, description, icon, sort, is_highlight, is_visible
programs            id, title, description, image_id, sort, is_visible
facilities          id, name, spec, quantity, image_id, sort, is_visible
learning_stages     id, class_label(X/XI/XII), title, description, sort
achievements        id, title, level, rank, year, student_name, image_id, description, sort
careers             id, label, sort
partners            id, name, logo_id, url, sort
gallery_items       id, media_id, caption, album, sort
staff               id, name, role, photo_id, bio, sort
faqs                id, question, answer, sort
alumni_feedback     id, name, graduation_year, occupation, message, photo_id, status, created_at
contact_messages    id, name, phone, email, message, type(pendaftaran/pertanyaan), status, created_at

categories          id, name, slug, description, seo_title, seo_description, sort
tags                id, name, slug
posts               id, title, slug, excerpt, body, featured_media_id, category_id, author_id,
                    status, published_at, scheduled_at, allow_comments,
                    focus_keyword, seo_title, seo_description, og_media_id, seo_score,
                    views_count, likes_count, unlikes_count, comments_count, shares_count,
                    like_percent, engagement_percent, stars
post_tag            post_id, tag_id
post_revisions      id, post_id, title, body, user_id, created_at
post_reactions      id, post_id, visitor_hash, value(+1/-1), created_at      -- unik (post_id, visitor_hash)
post_shares         id, post_id, channel, created_at
comments            id, post_id, parent_id, name, email, body, status,
                    visitor_hash, likes_count, is_admin_reply, created_at
comment_likes       id, comment_id, visitor_hash                              -- unik (comment_id, visitor_hash)
comment_reports     id, comment_id, reason, visitor_hash, created_at

visits              id, visited_at, path, post_id(null), visitor_hash, referrer_source, referrer_url,
                    device, browser, country, is_bot
daily_stats         date, visits, unique_visitors, pageviews
reports             id, type, period_start, period_end, file_path, status, generated_by, created_at
activity_logs       id, user_id, action, subject_type, subject_id, meta(json), created_at
```

Indeks penting: `posts(slug)`, `posts(status, published_at)`, `visits(visited_at)`, `visits(post_id, visited_at)`, `post_reactions(post_id, visitor_hash)`, `comments(post_id, status)`.

Gunakan **enum PHP** (backed enum) untuk status berita, status komentar, role, dan kanal bagikan.

---

## 11. Struktur proyek (monorepo, satu deploy)

```
tsm-cms/
├─ app/
│  ├─ Actions/                    -- PublishPost, ReactToPost, SubmitComment, GenerateReport, ...
│  ├─ Enums/                      -- PostStatus, CommentStatus, Role, ShareChannel
│  ├─ Http/
│  │  ├─ Controllers/
│  │  │  ├─ Api/V1/Public/        -- LandingController, PostController, ReactionController, CommentController, ...
│  │  │  ├─ Api/V1/Admin/         -- DashboardController, PostController, SeoController, MediaController, ReportController, ...
│  │  │  ├─ Api/V1/AuthController.php
│  │  │  └─ SeoShellController.php, SitemapController.php, FeedController.php
│  │  ├─ Middleware/              -- TrackVisitor, EnsureVisitorId, NoIndexAdmin
│  │  ├─ Requests/                -- FormRequest per aksi (validasi + pesan Indonesia)
│  │  └─ Resources/
│  │     ├─ Public/               -- PostResource, CommentResource (tanpa data sensitif)
│  │     └─ Admin/
│  ├─ Models/
│  ├─ Policies/
│  ├─ Services/
│  │  ├─ Seo/SeoAnalyzer.php      -- aturan bagian 7.2
│  │  ├─ RatingCalculator.php     -- Wilson, bagian 7.3
│  │  ├─ VisitorHasher.php
│  │  ├─ Reports/ReportBuilder.php, InsightGenerator.php
│  │  ├─ ImageOptimizer.php
│  │  └─ HtmlSanitizer.php
│  ├─ Jobs/                       -- OptimizeImage, SendCommentNotification, BuildReportFile, RecomputeRating
│  └─ Console/Commands/           -- PublishScheduledPosts, AggregateDailyStats, SendMonthlyReport
├─ bootstrap/ config/ database/ (migrations, factories, seeders)
├─ routes/
│  ├─ api.php                     -- prefix v1, grup public & admin
│  └─ web.php                     -- sitemap, robots, feed, shell SPA, catch-all
├─ resources/
│  ├─ views/spa/ (public.blade.php, admin.blade.php)   -- cangkang tipis + @vite
│  ├─ css/ tokens.css (dari DESIGN.md), base.css, public.css, admin.css
│  └─ js/
│     ├─ shared/                  -- api client (axios), query client, types, utils, components dasar
│     ├─ public/                  -- main.ts, router.ts, pages/, components/, composables/
│     │  └─ components/           -- WindowCard, StatStrip, CompetencyCard, StageCard, Chip, Button, Pill
│     └─ admin/                   -- main.ts, router.ts, pages/, components/, stores/
│        └─ components/           -- DataTable, RichEditor, SeoPanel, MediaPicker, ChartCard, ConfirmDialog
├─ tests/ (Feature/Api, Unit)     -- Pest
├─ vite.config.ts                 -- dua entry: resources/js/public/main.ts, resources/js/admin/main.ts
└─ composer.json, package.json
```

Catatan deploy: `npm run build` menghasilkan aset ke `public/build`. Server web mengarah ke `public/`. Cron: `* * * * * php artisan schedule:run`. Worker antrean: `php artisan queue:work` (via Supervisor atau cron `queue:work --stop-when-empty` di hosting terbatas).

---

## 12. Keamanan dan kualitas

**Keamanan**

- Validasi semua input dengan FormRequest. Sanitasi HTML (isi berita, komentar) dengan whitelist tag.
- Sanctum SPA: cookie `HttpOnly`/`Secure`/`SameSite`, perlindungan CSRF; tanpa token di `localStorage`.
- Rate limit pada login, reaksi, komentar, formulir, dan unggah.
- Unggah file: hanya jpg/png/webp (dan svg yang dibersihkan), cek MIME sebenarnya, batas ukuran, nama acak, gambar diproses ulang oleh server.
- Policy di setiap endpoint admin; Penulis hanya bisa mengubah berita miliknya.
- Hash kata sandi kuat, 2FA untuk Super Admin, HTTPS wajib, header keamanan (CSP, `X-Content-Type-Options`, `Referrer-Policy`).
- Hash pengunjung memakai rahasia server, tidak menyimpan IP mentah.
- Log aktivitas untuk semua aksi admin; `APP_DEBUG=false` di produksi.
- Audit dependensi (`composer audit`, `npm audit`) di CI.

**Kinerja**

- Cache respons `site/landing` dan daftar berita; invalidasi saat konten berubah.
- Gambar WebP berbagai ukuran (`srcset`), lazy-load, dimensi eksplisit agar tidak ada layout shift.
- *Code splitting* per rute, dua entry terpisah (publik dan admin).
- Target Lighthouse > 90 untuk Performance, SEO, Accessibility, Best Practices (SPA publik, seluler).

**Aksesibilitas dan responsif** sesuai `DESIGN.md` bagian 7: WCAG AA, fokus terlihat (`outline: 3px solid var(--sky)`), 360px tanpa scroll horizontal, `prefers-reduced-motion`, navigasi keyboard penuh di admin, `lang="id"`.

**Pengujian**

- Pest Feature test untuk **setiap endpoint API** (status, bentuk JSON, izin, validasi).
- Unit test: `SeoAnalyzer`, `RatingCalculator`, `VisitorHasher`, `HtmlSanitizer`.
- Uji khusus: satu suara per pengunjung, ganti suara, rate limit komentar, penolakan publikasi bila skor SEO di bawah ambang, data sensitif tidak bocor di API publik.
- Frontend: Vitest untuk komposabel dan komponen penting; Playwright untuk alur utama (login admin, tulis dan terbitkan berita, like dan komentar).

---

## 13. Panduan desain (kedua SPA)

Gunakan token `DESIGN.md` bagian 10 di `tokens.css`, dibagikan ke SPA publik dan admin.

- Latar `--bg` dengan grid blueprint, kartu putih radius 18px, border `--line`, bayangan biru.
- Admin: sidebar putih dengan logo TSM, item aktif berlatar `--bg` teks `--primary`.
- Tabel data memakai garis putus-putus `1px dashed var(--line)`; ID dan label teknis memakai JetBrains Mono.
- Tombol utama `--primary`, sekunder putih berborder; fokus `outline: 3px solid var(--sky)`.
- Dashboard memakai komponen **jendela** untuk grafik utama dan **strip statistik** untuk angka ringkasan.
- Status hijau/merah hanya untuk sukses/error; selebihnya biru dan baja.
- Transisi interaksi 0,2 detik; tanpa animasi masuk section.
- Skeleton loading memakai warna `--soft` dan `--line`, tanpa kilau berlebihan, dan mati saat `prefers-reduced-motion`.

---

## 14. Saran fitur tambahan

**Prioritas tinggi (versi pertama)**

1. Formulir pendaftaran/PPDB sederhana atau tombol WhatsApp terintegrasi (tugas utama landing page adalah memudahkan mendaftar).
2. Pustaka media dengan kompresi otomatis.
3. Mitra industri (DUDI): logo bengkel/dealer mitra PKL.
4. Galeri kegiatan.
5. Backup otomatis mingguan (database + media).
6. Log aktivitas.
7. Peta lokasi dan tombol rute.

**Prioritas menengah**

8. Profil guru dan instruktur.
9. Agenda/kalender kegiatan.
10. FAQ (dengan skema `FAQPage`).
11. Jejak lulusan (tracer study), otomatis memperbarui angka di strip statistik.
12. Berita terkait dan berita populer.
13. Pencarian situs.
14. Langganan berita via email atau saluran WhatsApp.
15. Laporan berkala berita lama yang perlu dioptimalkan SEO-nya.

**Prioritas rendah**

16. Multi-bahasa (Indonesia, Inggris).
17. Katalog layanan servis teaching factory dan booking.
18. Integrasi Google Search Console/Analytics (berdampingan dengan statistik internal).
19. PWA (pasang di HP).
20. Notifikasi real-time admin (komentar baru) dengan Laravel Reverb.

---

## 15. Tahapan pengerjaan (urutan untuk agent)

**Tahap 1 — Fondasi**
1. Inisialisasi Laravel 13, konfigurasi database, Sanctum SPA, Pest, Pint, Scramble.
2. Setup Vite dengan dua entry Vue 3 + TypeScript, `tokens.css` dari `DESIGN.md`.
3. Auth admin (login, logout, `me`, role, Policy) dan kerangka layout SPA admin.
4. Migrasi, enum, dan seeder data awal TSM SMKS Islam 1 Kota Blitar (tandai placeholder).

**Tahap 2 — Konten situs**
5. Pustaka media (unggah, kompres, varian WebP, alt).
6. API + halaman admin untuk semua section dan daftar item (hero, kompetensi, program, sarana, tahap, prestasi, karier, mitra, kontak, statistik), termasuk reorder.
7. `GET /site/landing` + SPA publik landing page membaca semua dari API, dengan saklar dan urutan section.
8. Cangkang SEO (`SeoShellController`), `sitemap.xml`, `robots.txt`.

**Tahap 3 — Berita**
9. API + admin berita: CRUD, kategori, tag, editor TipTap, draf, jadwal, revisi.
10. `SeoAnalyzer` + endpoint analisis + panel SEO real-time + aturan blokir publikasi di API.
11. SPA publik berita: daftar, filter, detail, berita terkait, meta + JSON-LD lewat shell.

**Tahap 4 — Interaksi**
12. Identitas pengunjung (`vid`), like/unlike, `RatingCalculator`, bagikan.
13. Komentar bersarang, like komentar, laporan, moderasi admin, anti-spam, notifikasi email (queue).
14. Umpan balik lulusan (formulir publik + moderasi admin) dan pesan/pendaftaran.

**Tahap 5 — Analitik dan laporan**
15. `/track`, rekap `daily_stats`, dashboard admin.
16. Laporan periodik: pratinjau, ekspor PDF/Excel via queue, laporan otomatis bulanan, `InsightGenerator`.

**Tahap 6 — Penyempurnaan**
17. Fitur prioritas tinggi sisanya (PPDB/WhatsApp, galeri, backup, log aktivitas, peta).
18. Pengujian menyeluruh (Pest, Vitest, Playwright), audit keamanan dan aksesibilitas, optimasi Lighthouse.
19. Dokumentasi: instalasi, deploy, panduan admin singkat, dokumentasi API (Scramble).

**Definisi selesai:** seluruh teks dan foto yang tampil di situs dapat diubah dari admin; semua fungsi tersedia lewat API terdokumentasi dan teruji; berita dapat dibuat dengan skor SEO, di-like/unlike, dibagikan, dikomentari dan dibalas; tautan berita tampil benar di pratinjau WhatsApp/Facebook dan terbaca crawler; dashboard dan laporan menampilkan data nyata; tampilan mengikuti `DESIGN.md`.

---

## 16. Prompt singkat untuk Hermes

> Bangun aplikasi CMS untuk jurusan Teknik Sepeda Motor SMKS Islam 1 Kota Blitar (Jl. Musi No. 06 Kota Blitar) mengikuti dokumen `CMS-TSM-BLITAR.md`, dengan tampilan sesuai `DESIGN.md`. Gunakan Laravel 13 (PHP 8.3+) sebagai API `/api/v1` dengan Sanctum mode SPA, dan Vue 3 + TypeScript + Vite untuk dua SPA (publik dan admin) dalam satu repositori. Semua teks dan foto harus berasal dari API dan bisa diubah lewat admin. Pastikan SEO tetap baik lewat cangkang SEO dari server (bagian 5.4). Kerjakan bertahap sesuai bagian 15, mulai dari Tahap 1. Untuk setiap endpoint, tulis Pest test dan dokumentasi Scramble. Jangan menambah warna atau gaya di luar `DESIGN.md`. Setelah tiap tahap selesai, jalankan pengujian dan laporkan ringkasan hasilnya sebelum lanjut.

---

## 17. Hal yang perlu disiapkan dari pihak sekolah

- Foto Kepala Konsentrasi Keahlian (resolusi baik, latar rapi) beserta nama dan gelar.
- Logo sekolah/jurusan, foto bengkel, alat, dan kegiatan praktik.
- Data asli: jumlah siswa, alumni terserap kerja, mitra industri, daftar prestasi, daftar sarana.
- Kontak: nomor WhatsApp pendaftaran, email, tautan media sosial, jadwal dan syarat pendaftaran.
- Hosting/VPS dengan **PHP 8.3 atau lebih baru**, MySQL, akses cron, dan domain dengan HTTPS.
- Pemilik konten: siapa jadi Super Admin, Editor, dan Penulis.
