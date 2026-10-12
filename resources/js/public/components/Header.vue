<template>
  <header class="site-header" :class="{ scrolled }">
    <div class="container header-in">
      <RouterLink to="/" class="brand" aria-label="Beranda TSM">
        <span class="logo-box" aria-hidden="true"></span>
        <span class="brand-text">
          <span class="brand-name">TSM</span>
          <span class="brand-sub">SMKS Islam 1 Blitar</span>
        </span>
      </RouterLink>

      <nav :class="{ open: menuOpen }" aria-label="Menu utama">
        <RouterLink v-for="m in menu" :key="m.to" :to="m.to" active-class="active" @click="menuOpen = false">
          {{ m.label }}
        </RouterLink>
      </nav>

      <a class="btn btn-primary btn-sm daftar" :href="`https://wa.me/${whatsapp}`" target="_blank" rel="noopener">
        Daftar sekarang
      </a>

      <button class="burger" :aria-expanded="menuOpen" aria-label="Buka menu" @click="menuOpen = !menuOpen">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

defineProps<{
  menu: Array<{ label: string; to: string }>;
  whatsapp?: string;
}>();

const menuOpen = ref(false);
const scrolled = ref(false);

function onScroll() {
  scrolled.value = window.scrollY > 8;
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<style scoped>
.site-header {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(255, 255, 255, 0.86);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid transparent;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.site-header.scrolled {
  border-bottom-color: var(--line);
  box-shadow: var(--shadow-sm);
}
.header-in {
  display: flex;
  align-items: center;
  gap: 24px;
  min-height: 68px;
}
.brand { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.brand:hover { text-decoration: none; }
.logo-box {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  background: var(--primary);
  position: relative;
  flex-shrink: 0;
}
.logo-box::after {
  content: '';
  position: absolute;
  inset: 9px;
  border: 3px solid #fff;
  border-radius: 50%;
  border-right-color: transparent;
}
.brand-text { display: flex; flex-direction: column; line-height: 1.1; }
.brand-name { font-weight: 800; font-size: 1.1rem; color: var(--ink); }
.brand-sub { font-size: 0.68rem; color: var(--muted); letter-spacing: 0.02em; }

nav { display: flex; gap: 4px; flex: 1; }
nav a {
  padding: 8px 14px;
  border-radius: var(--r-pill);
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--ink);
}
nav a:hover { background: var(--soft); text-decoration: none; }
nav a.active { background: var(--bg); color: var(--primary); }

.daftar { flex-shrink: 0; }

.burger {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: none;
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 8px 10px;
  cursor: pointer;
}
.burger span {
  display: block;
  width: 18px;
  height: 2px;
  border-radius: 2px;
  background: var(--ink);
}

@media (max-width: 860px) {
  .header-in { flex-wrap: wrap; gap: 12px; }
  .burger { display: flex; }
  .daftar { order: 3; }
  nav {
    display: none;
    order: 4;
    flex-basis: 100%;
    flex-direction: column;
    padding-bottom: 12px;
    gap: 2px;
  }
  nav.open { display: flex; }
  nav a { padding: 10px 14px; }
}
</style>
