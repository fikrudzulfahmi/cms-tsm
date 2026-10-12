# Design System: Jurusan Teknik Sepeda Motor

Dokumen ini adalah acuan desain untuk membangun landing page jurusan Teknik Sepeda Motor. Referensi visual: `index.html`. Semua halaman baru harus terasa satu keluarga dengan halaman itu.

## 1. Konsep

- **Subjek:** landing page jurusan Teknik Sepeda Motor di sebuah SMK.
- **Audiens:** calon siswa dan orang tua.
- **Tugas utama:** meyakinkan bahwa belajar di sini berarti praktik nyata, lalu memudahkan mereka mendaftar.
- **Karakter:** modern, industrial, cozy. Bersih dan terang, tegas tapi tidak keras. Industrial hadir lewat detail teknis (lembar kerja, spesifikasi, grid blueprint), bukan lewat warna mencolok atau bentuk kasar.
- **Bahasa:** Indonesia, jelas dan hangat, kalimat aktif, sentence case.

## 2. Warna

| Token | Hex | Fungsi |
|---|---|---|
| `--primary` | `#0D47A1` | Warna utama: tombol, judul aksen, blok penekanan, logo, ikon |
| `--primary-d` | `#0A3680` | Hover tombol utama |
| `--bg` | `#E3F2FD` | Background utama halaman |
| `--surface` | `#FFFFFF` | Kartu, jendela, strip, section bengkel |
| `--soft` | `#F3F9FE` | Bar jendela, hover tombol sekunder |
| `--ink` | `#0B1F3A` | Teks utama (biru sangat gelap, bukan hitam murni) |
| `--muted` | `#566A82` | Teks pendukung |
| `--steel` | `#8FA3B8` | Label teknis (mono), titik jendela |
| `--line` | `#C3D9EE` | Border dan pembatas |
| `--sky` | `#42A5F5` | Fokus keyboard, highlight gradien |

Aturan: hanya keluarga biru dan baja. Jangan tambah oranye, merah, atau warna lain kecuali untuk status error/sukses. Latar `--bg` dan `--surface` bergantian antar-section; `--primary` dipakai hemat (satu kartu penekanan di grid dan satu blok CTA).

Gradien hanya satu: `linear-gradient(135deg, var(--sky), var(--primary))` untuk bar progres.

## 3. Tipografi

- **Utama:** Plus Jakarta Sans (400, 500, 600, 800). Fallback: `system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif`.
- **Teknis:** JetBrains Mono (500). Dipakai untuk isi lembar kerja, tabel spesifikasi, nama jendela, dan label kelas. Jangan dipakai untuk paragraf.
- **Skala:**
  - H1: `clamp(2.4rem, 5.6vw, 4.2rem)`, weight 800, line-height 1.08, letter-spacing `-.03em`
  - H2: `clamp(1.8rem, 3.4vw, 2.2rem)`, weight 800, letter-spacing `-.02em`
  - H3: 1.15rem, weight 600
  - Body: 1rem sampai 1.08rem, line-height 1.6
  - Kecil: 0.85 sampai 0.92rem
- Panjang baris maksimal sekitar 52 sampai 65 karakter.
- Tidak ada huruf kapital semua, tidak ada kata berwarna di tengah judul.

## 4. Layout dan spacing

- Lebar konten: `width: min(1120px, 92%)`, rata tengah.
- Hero: dua kolom `1.1fr .9fr`, jarak 56px, rata kiri. Di bawah 860px menjadi satu kolom.
- Rata teks: kiri.
- Jarak antar-section: 88px di desktop, 56px di mobile.
- Header: logo kiri, navigasi dan tombol "Daftar sekarang" kanan, padding vertikal 26px.
- Footer: garis atas `--line`, teks kecil `--muted`.

## 5. Bentuk dan komponen

- **Radius:** 24px untuk jendela dan blok CTA, 18px untuk kartu, 99px untuk pill, tombol, dan chip, 11px untuk logo.
- **Bayangan:** `0 20px 50px -20px rgba(13,71,161,.38)` (berwarna biru, bukan abu-abu). Versi kecil: `0 10px 28px -16px rgba(13,71,161,.38)`.
- **Border:** 1px solid `--line`. Pembatas internal memakai garis putus-putus `1px dashed var(--line)` (kesan lembar kerja).
- **Latar halaman:** grid blueprint 36px dari dua `linear-gradient` garis 1px `rgba(13,71,161,.055)`, ditambah cahaya biru di pojok kanan atas `radial-gradient(60% 50% at 85% 0%, rgba(66,165,245,.28), transparent 70%)`.

Komponen inti:

1. **Logo:** kotak 34px radius 11px `--primary` dengan lingkaran putih terbuka (mirip roda/gir), diikuti teks `TSM` (ink) dan `.nusantara` (primary), weight 800.
2. **Pill status:** latar putih, border `--line`, teks `--primary` weight 600, titik biru berdenyut di kiri.
3. **Jendela (window card):** bar atas `--soft` dengan tiga titik `#B5CDE8` dan nama berkas gaya mono. Dipakai untuk lembar kerja servis (hero) dan tabel spesifikasi bengkel.
4. **Lembar kerja:** isi jendela hero. Daftar unit, keluhan, instruktur (mono, label `--steel`), checklist dengan kotak radius 5px (terisi biru jika selesai), dan bar progres.
5. **Strip statistik:** latar putih, border atas-bawah `--line`, empat kolom dipisah garis tipis. Angka weight 800 warna primary.
6. **Kartu kompetensi:** putih, border `--line`, radius 18px, ikon garis dalam kotak 40px `--bg`. Satu kartu pertama berlatar `--primary` sebagai penekanan.
7. **Kartu tahap:** seperti kartu kompetensi, dengan label kelas (mono, pill `--bg`) di atas judul.
8. **Chip karier:** putih, border `--line`, radius 99px, teks `--primary` weight 600.
9. **Tombol utama:** `--primary`, teks putih, weight 600, radius 99px, padding `12px 22px`. Hover `--primary-d`, active `scale(.97)`.
10. **Tombol sekunder:** putih, border `--line`, teks `--primary`.
11. **Blok CTA:** `--primary`, radius 24px, padding 56px, tombol putih di atasnya.

## 6. Motion

Hemat dan bertujuan.

- Satu momen ambient: jendela lembar kerja di hero melayang pelan (`translateY(-10px)`, 6 detik) dan titik status berdenyut.
- Interaksi (hover, fokus, klik) memakai transisi 0.2 detik.
- Tidak ada animasi masuk fade-and-slide pada section.
- Wajib hormati `prefers-reduced-motion: reduce` (matikan semua animasi).

## 7. Aksesibilitas dan kualitas

- Kontras teks memenuhi WCAG AA.
- Fokus keyboard terlihat: `outline: 3px solid var(--sky); outline-offset: 3px`.
- Responsif sampai lebar 360px, tanpa scroll horizontal.
- Gambar punya `alt`; elemen dekoratif memakai `aria-hidden`; jendela ilustratif memakai `role="img"` dengan `aria-label`.
- `lang="id"`, meta description, dan `theme-color` (`#0D47A1`) diisi.

## 8. Gaya penulisan

- Sapaan "kamu" kepada calon siswa.
- Judul menjelaskan isi, bukan slogan kosong. Baik: "Lima keahlian yang kamu kuasai". Buruk: "Wujudkan masa depan gemilang".
- Tombol memakai kata kerja spesifik: "Daftar sekarang", "Lihat kompetensi", "Isi formulir pendaftaran".
- Hindari label kapital semua dan eyebrow kecil di atas judul.
- Penomoran atau label tahap hanya untuk urutan nyata (kelas X, XI, XII).

## 9. Struktur halaman

```
[Header sticky: logo | menu | tombol Daftar]
[Hero: judul + CTA kiri | jendela lembar kerja kanan]
[Strip statistik 4 kolom]
[Kompetensi: grid 3 kolom, kartu pertama berlatar primary]
[Bengkel: teks kiri | jendela tabel spesifikasi kanan]
[Alur belajar: 3 kartu kelas X, XI, XII]
[Karier: chip]
[CTA: blok primary]
[Footer]
```

## 10. Token CSS siap pakai

```css
:root{
  --primary:#0D47A1; --primary-d:#0A3680; --bg:#E3F2FD; --surface:#FFFFFF; --soft:#F3F9FE;
  --ink:#0B1F3A; --muted:#566A82; --steel:#8FA3B8; --line:#C3D9EE; --sky:#42A5F5;
  --shadow:0 20px 50px -20px rgba(13,71,161,.38);
  --shadow-sm:0 10px 28px -16px rgba(13,71,161,.38);
  --r-lg:24px; --r-md:18px; --r-pill:99px;
}
body{
  font-family:'Plus Jakarta Sans',system-ui,sans-serif;
  color:var(--ink); background:var(--bg); line-height:1.6;
  background-image:
    radial-gradient(60% 50% at 85% 0%,rgba(66,165,245,.28),transparent 70%),
    linear-gradient(rgba(13,71,161,.055) 1px,transparent 1px),
    linear-gradient(90deg,rgba(13,71,161,.055) 1px,transparent 1px);
  background-size:auto,36px 36px,36px 36px;
  background-repeat:no-repeat,repeat,repeat;
}
```

## 11. Catatan untuk agent AI

- Nama sekolah, angka, nama instruktur, dan fasilitas di `index.html` adalah placeholder; ganti dengan data asli.
- Foto asli bengkel dan siswa boleh ditambahkan, dibungkus dalam komponen jendela (bar atas + radius 24px) agar tetap satu keluarga.
- Jangan menambah warna atau gaya di luar sistem ini.

## 12. Prompt singkat untuk agent

> Bangun landing page jurusan Teknik Sepeda Motor mengikuti `DESIGN.md` ini dan `index.html` sebagai referensi visual. Gunakan background `#E3F2FD`, warna utama `#0D47A1`, nuansa modern, industrial, dan cozy yang bersih, bahasa Indonesia. Buat responsif dan aksesibel, dan jangan menambah warna atau gaya di luar sistem ini.
