<template>
  <Header :settings="settings" :menu="menu" />
  <main id="main" class="container page">
    <div class="page-head">
      <span class="postcard mono">PRESTASI</span>
      <h1>Prestasi Siswa</h1>
      <p class="muted">Daftar prestasi siswa jurusan Teknik Sepeda Motor.</p>
    </div>

    <div class="chip-row">
      <button
        class="chip"
        :class="{ active: !year }"
        type="button"
        @click="setYear('')"
      >Semua tahun</button>
      <button
        v-for="y in years"
        :key="y"
        class="chip"
        :class="{ active: year === y }"
        type="button"
        @click="setYear(y)"
      >{{ y }}</button>
    </div>

    <div v-if="isPending" class="grid-3">
      <div v-for="i in 4" :key="i" class="card skeleton" aria-hidden="true"></div>
    </div>
    <div v-else-if="isError" class="card err-card">
      <p>{{ errorMessage }}</p>
      <button class="btn btn-primary btn-sm" type="button" @click="refetch()">Coba lagi</button>
    </div>
    <div v-else-if="items.length" class="grid-3">
      <article v-for="a in items" :key="a.id" class="card award">
        <div class="badge mono">{{ a.year }}</div>
        <h3>{{ a.title }}</h3>
        <p v-if="a.student_name" class="muted">Siswa: {{ a.student_name }}</p>
        <span v-if="a.level" class="chip level">{{ a.level }}</span>
      </article>
    </div>
    <div v-else class="card empty">
      <h2>Belum ada prestasi</h2>
      <p class="muted">Coba tahun lain.</p>
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

const year = ref('');

const { data, isPending, isError, error, refetch } = useQuery({
  queryKey: ['achievements', year],
  queryFn: async () =>
    (await api.get('/achievements', { params: { year: year.value || undefined } })).data?.data ?? [],
});

const items = computed(() => (data.value as Array<Record<string, unknown>>) ?? []);
const years = computed(() => {
  const all = items.value.map((a) => Number(a.year)).filter((y) => y > 0);
  return [...new Set(all)].sort((a, b) => b - a);
});
const errorMessage = computed(() => (isError.value ? extractErrorMessage(error.value) : ''));

function setYear(y: string) { year.value = y; }
</script>

<style scoped>
.page { padding: 72px 0 120px; }
.page-head { margin-bottom: 28px; }
.postcard {
  display: inline-block; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em;
  background: var(--bg); border: 1px dashed var(--line); color: var(--primary);
  border-radius: var(--r-pill); padding: 4px 12px; margin-bottom: 16px;
}
h1 { font-size: clamp(1.8rem, 4vw, 2.6rem); }
.muted { color: var(--muted); }
.chip-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 32px; }
.chip {
  font-size: 0.8rem; font-weight: 600; padding: 7px 14px;
  border: 1px solid var(--line); border-radius: var(--r-pill);
  background: var(--surface); color: var(--muted); cursor: pointer;
}
.chip.active { background: var(--primary); border-color: var(--primary); color: #fff; }
.grid-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 20px; }
.award { padding: 24px; }
.badge {
  display: inline-block; background: var(--bg); color: var(--primary);
  border: 1px dashed var(--line); border-radius: var(--r-pill);
  padding: 3px 12px; font-size: 0.74rem; margin-bottom: 14px;
}
.award h3 { margin-bottom: 8px; }
.level { margin-top: 12px; background: var(--surface); }
.skeleton { height: 180px; border-radius: var(--r-md); background: var(--bg); border: 1px dashed var(--line); }
.err-card { display: flex; flex-direction: column; align-items: flex-start; gap: 14px; }
.empty { text-align: center; padding: 56px 24px; }
</style>
