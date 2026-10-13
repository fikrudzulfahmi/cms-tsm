<template>
  <Header :settings="settings" :menu="menu" />
  <main id="main" class="container page">
    <div class="page-head">
      <span class="postcard mono">TENTANG</span>
      <h1>Tentang Jurusan</h1>
      <p class="muted">Jurusan Teknik Sepeda Motor — SMKS Islam 1 Blitar.</p>
    </div>

    <div v-if="tentang" class="card about-body" v-html="tentang.body"></div>

    <section class="section">
      <h2 class="sec-title">Kompetensi yang dipelajari</h2>
      <div class="grid-3">
        <article v-for="c in competencies" :key="c.id" class="card">
          <h3>{{ c.title }}</h3>
          <p class="muted">{{ c.description }}</p>
        </article>
      </div>
    </section>

    <section class="section">
      <h2 class="sec-title">Program belajar</h2>
      <div class="grid-3">
        <article v-for="p in programs" :key="p.id" class="card">
          <h3>{{ p.title }}</h3>
          <p class="muted">{{ p.description }}</p>
        </article>
      </div>
    </section>

    <section class="section">
      <h2 class="sec-title">Tenaga pengajar</h2>
      <div class="grid-staff">
        <article v-for="s in staff" :key="s.id" class="card staff-card">
          <div class="avatar" aria-hidden="true">{{ initials(s.name) }}</div>
          <div>
            <h3>{{ s.name }}</h3>
            <p v-if="s.role" class="role mono">{{ s.role }}</p>
            <p v-if="s.bio" class="muted bio">{{ s.bio }}</p>
          </div>
        </article>
      </div>
    </section>
  </main>
  <Footer :settings="settings" />
</template>

<script setup lang="ts">
import { SITE_MENU } from '@js/public/menu';

// satu sumber kebenaran menu -> navbar konsisten di semua halaman
const menu = SITE_MENU;
import { computed } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { api } from '@js/shared/api';
import Header from '../components/Header.vue';
import Footer from '../components/Footer.vue';

const { data } = useQuery({
  queryKey: ['landing'],
  queryFn: async () => (await api.get('/site/landing')).data?.data ?? {},
  staleTime: 5 * 60_000,
});

const settings = computed(() => data.value?.settings ?? {});
const competencies = computed(() => data.value?.kompetensi ?? []);
const programs = computed(() => data.value?.program ?? []);
const staff = computed(() => data.value?.staff ?? []);
const tentang = computed(() =>
  (data.value?.sections ?? []).find((s: { key: string }) => s.key === 'tentang'),
);

function initials(name?: string) {
  if (!name) return '?';
  return name.trim().split(/\s+/).slice(0, 2).map((w) => w[0]?.toUpperCase()).join('');
}
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

.about-body { max-width: 760px; padding: 28px 26px; margin-bottom: 8px; line-height: 1.8; }
.section { margin-top: 56px; }
.sec-title { margin-bottom: 22px; }
.grid-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
.grid-staff { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }

.staff-card { display: flex; gap: 16px; align-items: flex-start; padding: 22px; }
.avatar {
  flex-shrink: 0; width: 52px; height: 52px; border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--sky));
  color: #fff; font-weight: 700; font-size: 1.1rem;
  display: flex; align-items: center; justify-content: center;
}
.staff-card h3 { margin-bottom: 4px; }
.role { font-size: 0.72rem; color: var(--primary); letter-spacing: 0.03em; margin-bottom: 8px; }
.bio { font-size: 0.9rem; }
</style>
