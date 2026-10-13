<template>
  <div class="nf">
    <div class="container">
      <p class="code mono">404</p>
      <h1>Halaman tidak ditemukan</h1>
      <p class="muted">Maaf, halaman yang kamu cari tidak tersedia atau sudah dipindahkan.</p>
      <RouterLink class="btn btn-primary" to="/">Kembali ke beranda</RouterLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>

<style scoped>
.nf {
  min-height: 70vh; display: flex; align-items: center; justify-content: center;
  text-align: center; padding: 80px 0;
}
.code {
  font-size: clamp(3.4rem, 12vw, 6rem); font-weight: 800; color: var(--primary);
  line-height: 1; margin-bottom: 18px;
}
h1 { font-size: clamp(1.4rem, 3vw, 2rem); margin-bottom: 14px; }
.muted { color: var(--muted); max-width: 42ch; margin: 0 auto 28px; }
</style>
