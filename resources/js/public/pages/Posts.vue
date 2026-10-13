<template>
  <Header :settings="settings" :menu="menu" />
  <main id="main" class="container page">
    <div class="page-head">
      <span class="postcard mono">BERITA</span>
      <h1>Berita &amp; Kegiatan</h1>
      <p class="muted">Kabar terbaru jurusan Teknik Sepeda Motor.</p>
    </div>

    <div class="toolbar card">
      <div class="search-wrap">
        <input
          v-model="q"
          type="search"
          class="search"
          placeholder="Cari berita…"
          aria-label="Cari berita"
          @input="onSearch"
        />
      </div>
      <div class="chip-row">
        <button
          class="chip"
          :class="{ active: !categorySlug }"
          type="button"
          @click="setCategory('')"
        >
          Semua
        </button>
        <button
          v-for="c in categories"
          :key="c.slug"
          class="chip"
          :class="{ active: categorySlug === c.slug }"
          type="button"
          @click="setCategory(c.slug)"
        >
          {{ c.name }}
        </button>
      </div>
    </div>

    <div v-if="isPending" class="grid-3">
      <div v-for="i in 6" :key="i" class="card skeleton" aria-hidden="true"></div>
    </div>

    <div v-else-if="isError" class="card err-card">
      <p>{{ errorMessage }}</p>
      <button class="btn btn-primary btn-sm" type="button" @click="refetch()">
        Coba lagi
      </button>
    </div>

    <template v-else>
      <div v-if="posts.length" class="grid-3">
        <PostCard v-for="p in posts" :key="p.id" :post="p" />
      </div>
      <div v-else class="card empty">
        <h2>Belum ada berita</h2>
        <p class="muted">Coba kata kunci atau kategori lain.</p>
      </div>

      <nav v-if="lastPage > 1" class="pager" aria-label="Halaman">
        <button
          class="btn btn-secondary btn-sm"
          type="button"
          :disabled="page <= 1"
          @click="goPage(page - 1)"
        >
          Sebelumnya
        </button>
        <span class="mono page-num">Halaman {{ page }} / {{ lastPage }}</span>
        <button
          class="btn btn-secondary btn-sm"
          type="button"
          :disabled="page >= lastPage"
          @click="goPage(page + 1)"
        >
          Berikutnya
        </button>
      </nav>
    </template>
  </main>
  <Footer :settings="settings" />
</template>

<script setup lang="ts">
import { SITE_MENU } from '@js/public/menu';

// satu sumber kebenaran menu -> navbar konsisten di semua halaman
const menu = SITE_MENU;
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuery } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';
import Header from '../components/Header.vue';
import Footer from '../components/Footer.vue';
import PostCard from '../components/PostCard.vue';

const route = useRoute();
const router = useRouter();

const categorySlug = ref(String(route.query.kategori ?? route.params.slug ?? ''));
const q = ref(String(route.query.q ?? ''));
const page = ref(Number(route.query.page ?? 1));

const { data: landing } = useQuery({
  queryKey: ['landing'],
  queryFn: async () => (await api.get('/site/landing')).data?.data ?? {},
  staleTime: 5 * 60_000,
});
const settings = computed(() => landing.value?.settings ?? {});

const { data: categories } = useQuery({
  queryKey: ['categories'],
  queryFn: async () => (await api.get('/categories')).data?.data ?? [],
  staleTime: 60_000,
});

const { data, isPending, isError, error, refetch } = useQuery({
  queryKey: ['posts', categorySlug, q, page],
  queryFn: async () => {
    const res = await api.get('/posts', {
      params: {
        category: categorySlug.value || undefined,
        q: q.value || undefined,
        page: page.value,
        per_page: 9,
      },
    });
    return res.data;
  },
  placeholderData: (prev: unknown) => prev,
});

const posts = computed(() => data.value?.data ?? []);
const lastPage = computed(() => data.value?.meta?.last_page ?? data.value?.last_page ?? 1);

const errorMessage = computed(() =>
  isError.value ? extractErrorMessage(error.value) : '',
);

let searchTimer: ReturnType<typeof setTimeout> | null = null;
function onSearch() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    page.value = 1;
    router.replace({ query: { ...route.query, q: q.value, page: 1 } });
  }, 350);
}

function setCategory(slug: string) {
  categorySlug.value = slug;
  page.value = 1;
  router.replace({ query: { kategori: slug, q: q.value, page: 1 } });
}

function goPage(p: number) {
  page.value = p;
  router.replace({ query: { ...route.query, page: p } });
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

watch(
  () => [route.query.kategori, route.query.q, route.query.page],
  ([c, qq, p]) => {
    categorySlug.value = String(c ?? '');
    q.value = String(qq ?? '');
    page.value = Number(p ?? 1);
  },
);
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

.toolbar {
  display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
  margin-bottom: 32px; padding: 14px 18px;
}
.search {
  width: 260px; max-width: 100%; padding: 10px 14px;
  border: 1px solid var(--line); border-radius: var(--r-pill);
  background: var(--surface); color: var(--ink); font-size: 0.92rem;
  outline: none;
}
.search:focus { border-color: var(--sky); }
.chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
.chip {
  font-size: 0.8rem; font-weight: 600; padding: 7px 14px;
  border: 1px solid var(--line); border-radius: var(--r-pill);
  background: var(--surface); color: var(--muted); cursor: pointer;
}
.chip.active { background: var(--primary); border-color: var(--primary); color: #fff; }

.grid-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.skeleton { height: 300px; border-radius: var(--r-md); background: var(--bg); border: 1px dashed var(--line); }
.err-card { display: flex; flex-direction: column; align-items: flex-start; gap: 14px; }
.empty { text-align: center; padding: 56px 24px; }
.pager { display: flex; align-items: center; justify-content: center; gap: 18px; margin-top: 40px; }
.page-num { color: var(--muted); font-size: 0.85rem; }

@media (max-width: 860px) {
  .toolbar { flex-direction: column; align-items: stretch; }
  .search { width: 100%; }
}
</style>
