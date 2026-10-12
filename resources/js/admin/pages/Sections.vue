<template>
  <div class="grid">
    <div class="card">
      <div class="head">
        <div>
          <h2>Konten situs</h2>
          <p class="muted sub">Atur blok halaman depan: urutan, judul, dan tampil/sembunyi.</p>
        </div>
      </div>

      <ul class="sections">
        <li v-for="(s, i) in ordered" :key="s.key" :class="{ active: selected === s.key }">
          <div class="info">
            <strong>{{ s.title || keyLabel(s.key) }}</strong>
            <span class="mono key">{{ s.key }}</span>
            <span v-if="s.subtitle" class="muted sub-t">{{ s.subtitle }}</span>
          </div>
          <div class="tools">
            <button class="btn btn-secondary btn-sm" :disabled="i === 0" title="Naikkan" @click.stop="move(i, -1)">↑</button>
            <button class="btn btn-secondary btn-sm" :disabled="i === ordered.length - 1" title="Turunkan" @click.stop="move(i, 1)">↓</button>
            <label class="switch" :title="s.is_visible ? 'Tampil' : 'Sembunyi'">
              <input type="checkbox" :checked="s.is_visible" @change="toggleVisible(s, ($event.target as HTMLInputElement).checked)" />
              <span class="track"><span class="knob"></span></span>
            </label>
            <button class="btn btn-primary btn-sm" @click="openEdit(s.key)">Sunting</button>
          </div>
        </li>
        <li v-if="ordered.length === 0" class="muted empty">Memuat daftar bagian…</li>
      </ul>
    </div>

    <div class="card" v-if="selected">
      <div class="head">
        <h2>Ubah bagian <span class="mono key">{{ selected }}</span></h2>
      </div>

      <div v-if="loadingDetail" class="muted">Memuat…</div>
      <div v-else class="form">
        <label>
          <span>Judul</span>
          <input v-model="form.title" type="text" maxlength="160" placeholder="mis. Jurusan Teknik Sepeda Motor" />
        </label>
        <label>
          <span>Subjudul</span>
          <input v-model="form.subtitle" type="text" maxlength="200" placeholder="Kalimat pendukung di bawah judul" />
        </label>
        <label>
          <span>Isi</span>
          <textarea v-model="form.body" rows="8" placeholder="Teks isi bagian landing page…"></textarea>
        </label>
        <label class="check">
          <input type="checkbox" v-model="form.is_visible" />
          <span>Tampilkan bagian ini di halaman depan</span>
        </label>
        <p v-if="error" class="err">{{ error }}</p>
        <div class="foot">
          <button class="btn btn-primary btn-sm" :disabled="saving" @click="onSave">
            {{ saving ? 'Menyimpan…' : 'Simpan perubahan' }}
          </button>
        </div>
      </div>
    </div>

    <div class="card placeholder" v-else>
      <p class="muted">Pilih salah satu bagian di sebelah kiri untuk mengubah judul, subjudul, dan isinya.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { useRoute, useRouter } from 'vue-router';
import { api, extractErrorMessage } from '@js/shared/api';

interface SectionRow {
  key: string; title: string | null; subtitle: string | null;
  sort?: number; is_visible: boolean;
}

const qc = useQueryClient();
const route = useRoute();
const router = useRouter();

const selected = ref<string | null>((route.params.key as string) || null);

const { data } = useQuery<unknown>({
  queryKey: ['admin-sections'],
  queryFn: async () => (await api.get('/admin/sections')).data,
});

const rows = computed<SectionRow[]>(() => {
  const d = data.value as { data?: SectionRow[] } | SectionRow[] | null;
  if (!d) return [];
  if (Array.isArray(d)) return d;
  const inner = d.data;
  return Array.isArray(inner) ? inner : [];
});

const order = ref<string[] | null>(null);
const ordered = computed<SectionRow[]>(() => {
  const list = [...rows.value].sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0));
  if (!order.value) return list;
  return order.value
    .map((k) => list.find((s) => s.key === k))
    .filter((s): s is SectionRow => Boolean(s));
});

watch(selected, (key) => {
  if (key) void router.replace(`/admin/konten/${key}`);
  else void router.replace('/admin/konten');
});

// ---- detail form ----
const loadingDetail = ref(false);
const saving = ref(false);
const error = ref('');
const form = reactive<{ title: string; subtitle: string; body: string; is_visible: boolean }>({
  title: '', subtitle: '', body: '', is_visible: true,
});

async function openEdit(key: string) {
  selected.value = key;
  loadingDetail.value = true;
  error.value = '';
  try {
    const res = await api.get(`/admin/sections/${key}`);
    const s = (res.data?.data ?? res.data) as Record<string, unknown>;
    form.title = (s.title as string) ?? '';
    form.subtitle = (s.subtitle as string) ?? '';
    form.body = (s.body as string) ?? '';
    form.is_visible = s.is_visible !== false;
  } catch (e) {
    error.value = extractErrorMessage(e);
  } finally {
    loadingDetail.value = false;
  }
}

async function onSave() {
  if (!selected.value) return;
  saving.value = true;
  error.value = '';
  try {
    await api.put(`/admin/sections/${selected.value}`, {
      title: form.title || null,
      subtitle: form.subtitle || null,
      body: form.body || null,
      is_visible: form.is_visible,
    });
    qc.invalidateQueries({ queryKey: ['admin-sections'] });
  } catch (e) {
    error.value = extractErrorMessage(e);
  } finally {
    saving.value = false;
  }
}

async function toggleVisible(s: SectionRow, value: boolean) {
  try {
    await api.put(`/admin/sections/${s.key}`, {
      title: s.title, subtitle: s.subtitle, is_visible: value,
    });
    qc.invalidateQueries({ queryKey: ['admin-sections'] });
  } catch (e) { alert(extractErrorMessage(e)); }
}

function move(index: number, delta: number) {
  const list = [...ordered.value];
  const target = index + delta;
  if (target < 0 || target >= list.length) return;
  const [item] = list.splice(index, 1);
  list.splice(target, 0, item);
  order.value = list.map((s) => s.key);
  void saveOrder();
}

async function saveOrder() {
  if (!order.value) return;
  try {
    await api.patch('/admin/sections/order', { keys: order.value });
    order.value = null;
    qc.invalidateQueries({ queryKey: ['admin-sections'] });
  } catch (e) {
    order.value = null;
    alert(extractErrorMessage(e));
  }
}

function keyLabel(key: string): string {
  const map: Record<string, string> = {
    hero: 'Beranda (hero)', statistik: 'Statistik', kompetensi: 'Kompetensi',
    program: 'Program keahlian', fasilitas: 'Fasilitas', tahapan: 'Tahapan belajar',
    prestasi: 'Prestasi', karier: 'Prospek karier', mitra: 'Mitra industri',
    galeri: 'Galeri', staf: 'Guru & staf', faq: 'Pertanyaan umum',
    kontak: 'Kontak', umpan_balik: 'Umpan balik lulusan',
  };
  return map[key] ?? key;
}
</script>

<style scoped>
.grid { display: grid; grid-template-columns: minmax(320px, 1fr) minmax(320px, 1.1fr); gap: 20px; align-items: start; }
@media (max-width: 960px) { .grid { grid-template-columns: 1fr; } }
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 22px; box-shadow: var(--shadow-sm); }
.head { margin-bottom: 14px; }
.head h2 { font-size: 1.1rem; font-weight: 700; }
.sub { font-size: 0.78rem; }
.sections { list-style: none; display: flex; flex-direction: column; gap: 8px; }
.sections li { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 14px; border: 1px dashed var(--line); border-radius: 14px; }
.sections li.active { border-style: solid; border-color: var(--sky); background: var(--soft); }
.info { display: flex; flex-direction: column; min-width: 0; }
.info strong { font-size: 0.9rem; }
.key { font-size: 0.7rem; color: var(--muted); }
.sub-t { font-size: 0.72rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; }
.tools { display: flex; align-items: center; gap: 6px; }
.tools button { padding: 4px 10px; }
.switch { display: inline-flex; align-items: center; cursor: pointer; }
.switch input { position: absolute; opacity: 0; pointer-events: none; }
.track { width: 40px; height: 22px; border-radius: var(--r-pill); background: var(--line); position: relative; transition: background 0.2s ease; display: inline-block; }
.knob { position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; transition: left 0.2s ease; }
.switch input:checked + .track { background: var(--primary); }
.switch input:checked + .track .knob { left: 21px; }
.form { display: flex; flex-direction: column; gap: 14px; }
.form label { display: flex; flex-direction: column; gap: 6px; font-size: 0.8rem; font-weight: 600; color: var(--muted); }
.form input[type='text'], .form textarea { padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px; font-size: 0.85rem; color: var(--ink); font-weight: 400; }
.form .check { flex-direction: row; align-items: center; gap: 8px; }
.foot { display: flex; justify-content: flex-end; }
.placeholder { display: grid; place-items: center; min-height: 160px; text-align: center; }
.empty { padding: 18px 0; text-align: center; }
.btn { display: inline-flex; align-items: center; font-weight: 600; border-radius: var(--r-pill); padding: 8px 14px; border: 1px solid transparent; cursor: pointer; font-size: 0.8rem; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-secondary { background: var(--surface); color: var(--primary); border-color: var(--line); }
.btn-sm { padding: 6px 12px; font-size: 0.75rem; }
.btn:disabled { opacity: 0.45; cursor: not-allowed; }
.muted { color: var(--muted); }
.mono { font-family: var(--font-mono); }
.err { color: var(--danger); font-size: 0.78rem; }
</style>
