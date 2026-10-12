<template>
  <div class="card">
    <div class="head">
      <h2>Semua berita</h2>
      <RouterLink to="/admin/berita/baru" class="btn btn-primary btn-sm">Tulis berita</RouterLink>
    </div>

    <div class="filters">
      <input v-model="q" type="search" placeholder="Cari judul…" @input="refetch" />
      <select v-model="status" @change="refetch">
        <option value="">Semua status</option>
        <option value="draf">Draf</option>
        <option value="menunggu_review">Menunggu review</option>
        <option value="terbit">Terbit</option>
        <option value="arsip">Arsip</option>
      </select>
    </div>

    <table class="data-table">
      <thead>
        <tr><th>Judul</th><th>Status</th><th class="num">Skor SEO</th><th class="num">Dilihat</th><th>Penulis</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="p in data?.data" :key="p.id">
          <td>
            <RouterLink :to="`/admin/berita/${p.id}/sunting`" class="title-link">{{ p.title }}</RouterLink>
            <span class="mono slug">{{ p.slug }}</span>
          </td>
          <td><span class="chip mono" :class="p.status">{{ statusLabel(p.status) }}</span></td>
          <td class="mono num" :class="scoreClass(p.seo_score)">{{ p.seo_score }}</td>
          <td class="mono num">{{ fmt(p.views_count) }}</td>
          <td>{{ p.author?.name ?? '—' }}</td>
          <td class="acts">
            <button v-if="p.status !== 'terbit'" class="btn btn-secondary btn-sm" @click="onPublish(p.id)">Terbitkan</button>
            <button class="btn btn-ghost btn-sm" @click="onDelete(p.id)">Hapus</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="pager">
      <button class="btn btn-secondary btn-sm" :disabled="!data?.prev_page_url" @click="goPage(data?.current_page - 1)">Sebelumnya</button>
      <span class="mono muted">hal. {{ data?.current_page ?? 1 }} / {{ data?.last_page ?? 1 }}</span>
      <button class="btn btn-secondary btn-sm" :disabled="!data?.next_page_url" @click="goPage(data?.current_page + 1)">Berikutnya</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';
import { RouterLink } from 'vue-router';

const qc = useQueryClient();
const q = ref('');
const status = ref('');
const page = ref(1);

interface PostRow {
  id: number; title: string; slug: string; status: string; seo_score: number;
  views_count: number; author: { name: string } | null;
}
interface Paginated { data: PostRow[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null }

const { data, refetch } = useQuery<Paginated>({
  queryKey: ['admin-posts', { q, status, page }],
  queryFn: async () => (await api.get('/admin/posts', { params: { q: q.value, status: status.value, page: page.value, per_page: 15 } })).data,
});

function goPage(n?: number) {
  if (n && n >= 1) { page.value = n; void refetch(); }
}
async function onPublish(id: number) {
  try {
    await api.post(`/admin/posts/${id}/publish`);
    qc.invalidateQueries({ queryKey: ['admin-posts'] });
  } catch (e) { alert(extractErrorMessage(e)); }
}
async function onDelete(id: number) {
  if (!confirm('Hapus berita ini? Tindakan ini tidak bisa dibatalkan.')) return;
  try {
    await api.delete(`/admin/posts/${id}`);
    qc.invalidateQueries({ queryKey: ['admin-posts'] });
  } catch (e) { alert(extractErrorMessage(e)); }
}
function statusLabel(s: string): string {
  const map: Record<string, string> = { draf: 'Draf', menunggu_review: 'Review', terbit: 'Terbit', arsip: 'Arsip', terjadwal: 'Terjadwal' };
  return map[s] ?? s;
}
function fmt(n: number): string { return (n ?? 0).toLocaleString('id-ID'); }
function scoreClass(s: number): string {
  return s >= 80 ? 'good' : s >= 50 ? 'mid' : 'low';
}
</script>

<style scoped>
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 24px; box-shadow: var(--shadow-sm); }
.head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.filters { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.filters input, .filters select { padding: 8px 12px; border: 1px solid var(--line); border-radius: 12px; font-size: 0.85rem; }
.filters input { min-width: 220px; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.data-table th { text-align: left; color: var(--muted); font-size: 0.72rem; padding: 8px 0; border-bottom: 1px solid var(--line); }
.data-table td { padding: 12px 0; border-bottom: 1px dashed var(--line); vertical-align: middle; }
.num { text-align: right; }
.title-link { font-weight: 600; }
.slug { display: block; font-size: 0.72rem; color: var(--muted); }
.chip { display: inline-flex; align-items: center; background: var(--bg); border: 1px solid var(--line); border-radius: var(--r-pill); padding: 3px 10px; font-size: 0.7rem; color: var(--primary); font-weight: 700; }
.chip.terbit { background: rgba(22, 163, 74, 0.12); color: var(--success); border-color: rgba(22, 163, 74, 0.3); }
.good { color: var(--success); }
.mid { color: var(--warn); }
.low { color: var(--danger); }
.acts { display: flex; gap: 6px; }
.btn { display: inline-flex; align-items: center; font-weight: 600; border-radius: var(--r-pill); padding: 8px 14px; border: 1px solid transparent; cursor: pointer; font-size: 0.8rem; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-secondary { background: var(--surface); color: var(--primary); border-color: var(--line); }
.btn-ghost { background: transparent; color: var(--danger); }
.btn-sm { padding: 6px 12px; font-size: 0.75rem; }
.pager { display: flex; align-items: center; gap: 12px; justify-content: center; margin-top: 20px; }
.muted { color: var(--muted); }
.mono { font-family: var(--font-mono); }
</style>
