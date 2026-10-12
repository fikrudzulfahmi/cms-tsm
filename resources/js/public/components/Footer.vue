<template>
  <footer class="site-footer">
    <div class="container footer-in">
      <div class="brand-col">
        <span class="logo-box" aria-hidden="true"></span>
        <div>
          <strong class="brand-name">Jurusan Teknik Sepeda Motor</strong>
          <p class="brand-sub">SMKS Islam 1 Blitar</p>
        </div>
      </div>

      <div class="cols">
        <div v-if="settings.school_address">
          <h4>Alamat</h4>
          <p>{{ settings.school_address }}</p>
        </div>
        <div>
          <h4>Kontak</h4>
          <p v-if="settings.school_whatsapp">WhatsApp: {{ settings.school_whatsapp }}</p>
          <p v-if="settings.school_email">Email: {{ settings.school_email }}</p>
        </div>
        <div>
          <h4>Jelajah</h4>
          <nav aria-label="Menu footer">
            <RouterLink to="/">Beranda</RouterLink>
            <RouterLink to="/berita">Berita</RouterLink>
            <RouterLink to="/prestasi">Prestasi</RouterLink>
            <RouterLink to="/kontak">Kontak</RouterLink>
          </nav>
        </div>
      </div>
    </div>

    <div class="container bar">
      <p class="muted">© {{ year }} {{ settings.site_name ?? 'Jurusan Teknik Sepeda Motor' }}. Hak cipta dilindungi.</p>
      <p class="muted mono" v-if="settings.footer_text">{{ settings.footer_text }}</p>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps<{
  settings?: Record<string, unknown> | null;
}>();

const settings = computed(() => props.settings ?? {});
const year = new Date().getFullYear();
</script>

<style scoped>
.site-footer {
  background: var(--surface);
  border-top: 1px solid var(--line);
  margin-top: auto;
  padding-top: 48px;
}
.footer-in {
  display: grid;
  grid-template-columns: 0.9fr 1.6fr;
  gap: 48px;
  padding-bottom: 32px;
}
.brand-col { display: flex; gap: 14px; align-items: flex-start; }
.logo-box {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: var(--primary);
  position: relative;
  flex-shrink: 0;
}
.logo-box::after {
  content: '';
  position: absolute;
  inset: 10px;
  border: 3px solid #fff;
  border-radius: 50%;
  border-right-color: transparent;
}
.brand-name { display: block; color: var(--ink); font-size: 0.95rem; }
.brand-sub { color: var(--muted); font-size: 0.82rem; }

.cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; }
h4 { font-size: 0.8rem; color: var(--muted); font-weight: 700; margin-bottom: 10px; letter-spacing: 0.04em; text-transform: uppercase; }
p { font-size: 0.85rem; color: var(--ink); line-height: 1.6; }
nav { display: flex; flex-direction: column; gap: 6px; }
nav a { font-size: 0.85rem; color: var(--ink); }
nav a:hover { color: var(--primary); }

.bar {
  border-top: 1px solid var(--line);
  padding-block: 18px;
  display: flex;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.bar p { font-size: 0.78rem; }
.muted { color: var(--muted); }
.mono { font-family: var(--font-mono); }

@media (max-width: 860px) {
  .footer-in { grid-template-columns: 1fr; gap: 32px; }
  .cols { grid-template-columns: 1fr 1fr; gap: 24px; }
}
@media (max-width: 480px) {
  .cols { grid-template-columns: 1fr; }
}
</style>
