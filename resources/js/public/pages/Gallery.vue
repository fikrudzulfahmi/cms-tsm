<template>
  <Header :settings="settings" :menu="menu" />
  <main id="main" class="container page">
    <div class="page-head">
      <span class="postcard mono">GALERI</span>
      <h1>Galeri Foto</h1>
      <p class="muted">Dokumentasi kegiatan dan praktik di bengkel sekolah.</p>
    </div>

    <div v-if="isPending" class="gallery-grid">
      <div v-for="i in 6" :key="i" class="skeleton" aria-hidden="true"></div>
    </div>
    <div v-else-if="isError" class="card err-card">
      <p>{{ errorMessage }}</p>
      <button class="btn btn-primary btn-sm" type="button" @click="refetch()">Coba lagi</button>
    </div>
    <div v-else-if="items.length" class="gallery-grid">
      <figure
        v-for="g in items"
        :key="g.id"
        class="photo-card card"
        @click="open(g)"
      >
        <img :src="String(g.image?.url ?? '')" :alt="String(g.image?.alt ?? g.caption ?? 'Foto galeri')" loading="lazy" />
        <figcaption v-if="g.caption">{{ g.caption }}</figcaption>
      </figure>
    </div>
    <div v-else class="card empty">
      <h2>Belum ada foto</h2>
    </div>

    <div v-if="lightbox" class="lightbox" @click="close" role="dialog" aria-modal="true">
      <img :src="lightbox.url" :alt="lightbox.alt ?? 'Foto'" />
      <p v-if="lightbox.caption" class="lb-cap">{{ lightbox.caption }}</p>
      <button class="btn btn-secondary lb-close" type="button" @click="close">Tutup</button>
    </div>
  </main>
  <Footer :settings="settings" />
</template>

<script setup lang="ts">
import { SITE_MENU } from '@js/public/menu';

// satu sumber kebenaran menu -> navbar konsisten di semua halaman
const menu = SITE_MENU;
import { computed, ref } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';
import Header from '../components/Header.vue';
import Footer from '../components/Footer.vue';

const { data: landing } = useQuery({
  queryKey: ['landing'],
  queryFn: async () => (await api.get('/site/landing')).data?.data ?? {},
  staleTime: 5 * 60_000,
});
const settings = computed(() => landing.value?.settings ?? {});

const { data, isPending, isError, error, refetch } = useQuery({
  queryKey: ['gallery'],
  queryFn: async () => {
    const res = await api.get('/gallery');
    return res.data?.data ?? res.data ?? [];
  },
});

const items = computed(() => (data.value as Array<Record<string, unknown>>) ?? []);
const errorMessage = computed(() => (isError.value ? extractErrorMessage(error.value) : ''));

const lightbox = ref<Record<string, unknown> | null>(null);
function open(g: Record<string, unknown>) { lightbox.value = g; }
function close() { lightbox.value = null; }
</script>

<style scoped>
.page { padding: 72px 0 120px; }
.page-head { margin-bottom: 32px; }
.postcard {
  display: inline-block; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em;
  background: var(--bg); border: 1px dashed var(--line); color: var(--primary);
  border-radius: var(--r-pill); padding: 4px 12px; margin-bottom: 16px;
}
h1 { font-size: clamp(1.8rem, 4vw, 2.6rem); }
.muted { color: var(--muted); }

.gallery-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 18px;
}
.photo-card { padding: 0; overflow: hidden; cursor: pointer; transition: transform .18s ease; }
.photo-card:hover { transform: translateY(-4px); }
.photo-card img {
  display: block; width: 100%; height: auto; aspect-ratio: 4 / 3; object-fit: cover;
}
.photo-card figcaption {
  padding: 12px 14px; font-size: 0.85rem; color: var(--muted);
}
.skeleton { height: 170px; border-radius: var(--r-md); background: var(--bg); border: 1px dashed var(--line); }

.lightbox {
  position: fixed; inset: 0; z-index: 999;
  background: rgba(11, 31, 58, 0.9);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 14px; padding: 24px;
}
.lightbox img {
  max-width: 90vw; max-height: 76vh; border-radius: var(--r-md);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
}
.lb-cap { color: #fff; font-size: 0.9rem; }
.lb-close { position: absolute; top: 22px; right: 22px; }

.err-card { display: flex; flex-direction: column; align-items: flex-start; gap: 14px; }
.empty { text-align: center; padding: 56px 24px; }
</style>
