<template>
  <div class="card">
    <div class="head">
      <div>
        <h2>Pustaka media</h2>
        <p class="muted sub">JPG, PNG, atau WebP. Ukuran maksimal 8&nbsp;MB per berkas.</p>
      </div>
      <div class="upload">
        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" @change="onPick" />
        <button class="btn btn-primary btn-sm" :disabled="uploading || !file" @click="onUpload">
          {{ uploading ? 'Mengunggah…' : 'Unggah' }}
        </button>
      </div>
    </div>

    <div v-if="progress > 0 && progress < 100" class="bar"><span :style="{ width: progress + '%' }"></span></div>
    <p v-if="uploadError" class="err">{{ uploadError }}</p>

    <div class="filters">
      <input v-model="q" type="search" placeholder="Cari alt atau keterangan…" @input="refetch" />
    </div>

    <div class="grid-photo">
      <figure v-for="m in rows" :key="m.id" class="item">
        <img :src="m.url" :alt="m.alt ?? ''" loading="lazy" />
        <figcaption>
          <input
            class="edit"
            :value="m.alt ?? ''"
            placeholder="Teks alternatif…"
            @change="saveMeta(m, { alt: ($event.target as HTMLInputElement).value })"
          />
          <input
            class="edit"
            :value="m.caption ?? ''"
            placeholder="Keterangan…"
            @change="saveMeta(m, { caption: ($event.target as HTMLInputElement).value })"
          />
          <div class="meta">
            <span class="mono">{{ m.width ?? '?' }}×{{ m.height ?? '?' }}</span>
            <span class="mono">{{ sizeLabel(m.size) }}</span>
          </div>
          <button class="btn btn-ghost btn-sm" @click="onDelete(m)">Hapus</button>
        </figcaption>
      </figure>
      <p v-if="rows.length === 0" class="muted empty">Belum ada media. Unggah foto pertama.</p>
    </div>

    <div class="pager">
      <button class="btn btn-secondary btn-sm" :disabled="!data?.prev_page_url" @click="goPage((data?.current_page ?? 1) - 1)">Sebelumnya</button>
      <span class="mono muted">hal. {{ data?.current_page ?? 1 }} / {{ data?.last_page ?? 1 }}</span>
      <button class="btn btn-secondary btn-sm" :disabled="!data?.next_page_url" @click="goPage((data?.current_page ?? 1) + 1)">Berikutnya</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';

interface MediaRow {
  id: number; url: string; alt: string | null; caption: string | null;
  width: number | null; height: number | null; mime: string | null; size: number | null;
}
interface Paginated {
  data: MediaRow[]; current_page: number; last_page: number;
  prev_page_url: string | null; next_page_url: string | null;
}

const qc = useQueryClient();
const q = ref('');
const page = ref(1);
const fileInput = ref<HTMLInputElement | null>(null);
const file = ref<File | null>(null);
const uploading = ref(false);
const progress = ref(0);
const uploadError = ref('');

const { data, refetch } = useQuery<Paginated>({
  queryKey: ['admin-media', { q, page }],
  queryFn: async () => (await api.get('/admin/media', { params: { q: q.value, page: page.value, per_page: 24 } })).data,
});

const rows = computed<MediaRow[]>(() => data.value?.data ?? []);

function goPage(n: number) {
  if (n >= 1) { page.value = n; void refetch(); }
}

function onPick(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0] ?? null;
  file.value = f;
  uploadError.value = '';
}

async function onUpload() {
  if (!file.value) return;
  if (file.value.size > 8 * 1024 * 1024) { uploadError.value = 'Ukuran berkas melebihi 8 MB.'; return; }
  uploading.value = true;
  uploadError.value = '';
  progress.value = 0;
  try {
    const fd = new FormData();
    fd.append('file', file.value);
    await api.post('/admin/media', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (evt: { loaded: number; total?: number }) => {
        const total = evt.total ?? file.value?.size ?? 0;
        progress.value = total ? Math.round((evt.loaded / total) * 100) : 0;
      },
    });
    file.value = null;
    if (fileInput.value) fileInput.value.value = '';
    qc.invalidateQueries({ queryKey: ['admin-media'] });
  } catch (e) {
    uploadError.value = extractErrorMessage(e);
  } finally {
    uploading.value = false;
    progress.value = 0;
  }
}

async function saveMeta(m: MediaRow, patchData: { alt?: string; caption?: string }) {
  try {
    await api.patch(`/admin/media/${m.id}`, {
      alt: patchData.alt ?? m.alt ?? null,
      caption: patchData.caption ?? m.caption ?? null,
    });
    qc.invalidateQueries({ queryKey: ['admin-media'] });
  } catch (e) { alert(extractErrorMessage(e)); }
}

async function onDelete(m: MediaRow) {
  if (!confirm('Hapus berkas ini dari pustaka media? Tindakan ini permanen.')) return;
  try {
    await api.delete(`/admin/media/${m.id}`);
    qc.invalidateQueries({ queryKey: ['admin-media'] });
  } catch (e) { alert(extractErrorMessage(e)); }
}

function sizeLabel(bytes: number | null): string {
  if (!bytes) return '—';
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
</script>

<style scoped>
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 24px; box-shadow: var(--shadow-sm); }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.head h2 { font-size: 1.15rem; font-weight: 700; }
.sub { font-size: 0.8rem; }
.upload { display: flex; align-items: center; gap: 10px; }
.upload input[type='file'] { font-size: 0.78rem; max-width: 240px; }
.filters { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.filters input { padding: 8px 12px; border: 1px solid var(--line); border-radius: 12px; font-size: 0.85rem; min-width: 220px; }
.bar { height: 6px; border-radius: var(--r-pill); background: var(--bg); overflow: hidden; margin-bottom: 14px; }
.bar span { display: block; height: 100%; background: var(--primary); transition: width 0.2s ease; }
.grid-photo { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; }
.item { border: 1px dashed var(--line); border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; background: var(--soft); }
.item img { width: 100%; height: 130px; object-fit: cover; display: block; }
figcaption { padding: 10px; display: flex; flex-direction: column; gap: 6px; }
.edit { width: 100%; padding: 6px 8px; border: 1px solid var(--line); border-radius: 8px; font-size: 0.74rem; background: var(--surface); }
.meta { display: flex; justify-content: space-between; font-size: 0.68rem; color: var(--muted); }
.empty { grid-column: 1 / -1; padding: 24px 0; text-align: center; }
.pager { display: flex; align-items: center; gap: 12px; justify-content: center; margin-top: 20px; }
.btn { display: inline-flex; align-items: center; font-weight: 600; border-radius: var(--r-pill); padding: 8px 14px; border: 1px solid transparent; cursor: pointer; font-size: 0.8rem; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-secondary { background: var(--surface); color: var(--primary); border-color: var(--line); }
.btn-ghost { background: transparent; color: var(--danger); align-self: flex-start; padding-left: 0; }
.btn-sm { padding: 6px 12px; font-size: 0.75rem; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.muted { color: var(--muted); }
.mono { font-family: var(--font-mono); }
.err { color: var(--danger); font-size: 0.8rem; margin-bottom: 12px; }
</style>
