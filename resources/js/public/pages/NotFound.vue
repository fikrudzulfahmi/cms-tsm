<!-- PLACEHOLDER halaman publik — dibuat agar `npm run build` hijau.
     Ganti dengan implementasi sebenarnya pada tugas halaman publik. -->
<template>
  <section class="container page">
    <p class="postcard mono">PLACEHOLDER</p>
    <h1>Halaman tidak ditemukan</h1>
    <p class="muted">Alamat yang kamu tuju tidak tersedia.</p>
  </section>
</template>

<script setup lang="ts">
// Halaman NotFound belum diimplementasikan.
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
