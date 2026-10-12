<!-- PLACEHOLDER halaman publik — dibuat agar `npm run build` hijau.
     Ganti dengan implementasi sebenarnya pada tugas halaman publik. -->
<template>
  <section class="container page">
    <p class="postcard mono">PLACEHOLDER</p>
    <h1>Berita</h1>
    <p class="muted">Daftar berita jurusan akan ditampilkan di sini.</p>
  </section>
</template>

<script setup lang="ts">
// Halaman Posts belum diimplementasikan.
</script>

<style scoped>
.page { padding: 80px 0 120px; }
.postcard {
  display: inline-block; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em;
  background: var(--bg); border: 1px dashed var(--line); color: var(--primary);
  border-radius: var(--r-pill); padding: 4px 12px; margin-bottom: 16px;
}
h1 { font-size: clamp(1.8rem, 4vw, 2.6rem); }
.muted { color: var(--muted); margin-top: 12px; max-width: 60ch; }
</style>
