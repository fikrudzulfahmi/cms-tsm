<template>
  <div class="card">
    <div class="head">
      <div>
        <h2>Pengaturan</h2>
        <p class="muted sub">Perubahan langsung dipakai situs publik setelah disimpan.</p>
      </div>
      <div class="tabs">
        <button v-for="g in groups" :key="g.value" :class="{ active: group === g.value }" @click="switchGroup(g.value)">
          {{ g.label }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="muted">Memuat pengaturan…</div>

    <div v-else class="form">
      <label v-for="field in fields" :key="field.key">
        <span>{{ field.label }}</span>

        <template v-if="field.type === 'boolean'">
          <span class="check">
            <input type="checkbox" v-model="values[field.key]" />
            <span>{{ values[field.key] ? 'Aktif' : 'Nonaktif' }}</span>
          </span>
        </template>

        <template v-else-if="field.type === 'lines'">
          <textarea v-model="values[field.key]" rows="5" :placeholder="field.placeholder"></textarea>
          <span class="hint">Satu kata per baris.</span>
        </template>

        <template v-else-if="field.type === 'number'">
          <input v-model.number="values[field.key]" type="number" min="0" :placeholder="field.placeholder" />
        </template>

        <template v-else-if="field.type === 'long'">
          <textarea v-model="values[field.key]" rows="3" :placeholder="field.placeholder"></textarea>
        </template>

        <template v-else>
          <input v-model="values[field.key]" type="text" :placeholder="field.placeholder" />
        </template>
      </label>

      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="saved" class="ok">Pengaturan tersimpan.</p>

      <div class="foot">
        <button class="btn btn-primary btn-sm" :disabled="saving" @click="onSave">
          {{ saving ? 'Menyimpan…' : 'Simpan pengaturan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { useRoute, useRouter } from 'vue-router';
import { api, extractErrorMessage } from '@js/shared/api';

interface Field {
  key: string; label: string;
  type: 'text' | 'long' | 'number' | 'boolean' | 'lines';
  placeholder?: string;
}

const GROUPS: Record<string, Field[]> = {
  umum: [
    { key: 'site_name', label: 'Nama situs', type: 'text' },
    { key: 'site_full_name', label: 'Nama lengkap situs', type: 'text' },
    { key: 'school_address', label: 'Alamat sekolah', type: 'long' },
    { key: 'school_whatsapp', label: 'WhatsApp sekolah', type: 'text', placeholder: '62812xxxxxxx' },
    { key: 'school_email', label: 'Email sekolah', type: 'text' },
    { key: 'service_hours', label: 'Jam layanan', type: 'text' },
    { key: 'tagline', label: 'Tagline', type: 'long' },
    { key: 'cta_daftar_text', label: 'Teks tombol daftar', type: 'text' },
    { key: 'footer_text', label: 'Teks footer', type: 'long' },
  ],
  seo: [
    { key: 'seo_title', label: 'Judul SEO', type: 'text' },
    { key: 'seo_description', label: 'Deskripsi SEO', type: 'long' },
    { key: 'seo_keywords', label: 'Kata kunci SEO', type: 'long', placeholder: 'pisahkan dengan koma' },
    { key: 'seo_publish_min_score', label: 'Skor SEO minimum untuk terbit', type: 'number' },
    { key: 'seo_warn_threshold', label: 'Ambang peringatan SEO', type: 'number' },
  ],
  komentar: [
    { key: 'comment_auto_approve', label: 'Auto-setujui komentar', type: 'boolean' },
    { key: 'comment_rate_limit', label: 'Batas komentar per jam per pengunjung', type: 'number' },
    { key: 'comment_max_links', label: 'Maksimum tautan per komentar', type: 'number' },
    { key: 'banned_words', label: 'Kata terlarang (pisahkan baris)', type: 'lines' },
  ],
  kontak: [
    { key: 'google_maps_url', label: 'URL Google Maps (embed)', type: 'long' },
  ],
};

const LABELS: Record<string, string> = {
  site_name: 'Nama situs', school_address: 'Alamat sekolah', school_whatsapp: 'WhatsApp sekolah',
  school_email: 'Email sekolah', service_hours: 'Jam layanan', tagline: 'Tagline',
  footer_text: 'Teks footer', seo_title: 'Judul SEO', seo_description: 'Deskripsi SEO',
  seo_keywords: 'Kata kunci SEO', seo_publish_min_score: 'Skor SEO minimum untuk terbit',
  comment_auto_approve: 'Auto-setujui komentar', comment_max_links: 'Maksimum tautan per komentar',
  banned_words: 'Kata terlarang (pisahkan baris)',
};

const route = useRoute();
const router = useRouter();
const qc = useQueryClient();

const group = computed(() => (route.params.group as string) || 'umum');
const groups = [
  { value: 'umum', label: 'Umum' },
  { value: 'seo', label: 'SEO' },
  { value: 'komentar', label: 'Komentar' },
];

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const saved = ref(false);
const values = reactive<Record<string, string | number | boolean>>({});
const extraKeys = ref<string[]>([]);

const { data, refetch } = useQuery<Record<string, unknown>>({
  queryKey: ['admin-settings', group],
  queryFn: async () => {
    const res = await api.get(`/admin/settings/${group.value}`);
    return (res.data?.data ?? res.data) as Record<string, unknown>;
  },
});

const fields = computed<Field[]>(() => {
  const known = GROUPS[group.value] ?? [];
  const knownKeys = new Set(known.map((f) => f.key));
  const extras: Field[] = extraKeys.value
    .filter((k) => !knownKeys.has(k))
    .map((k) => ({ key: k, label: LABELS[k] ?? k.replace(/_/g, ' '), type: guessType(k) }));
  return [...known, ...extras];
});

function guessType(key: string): Field['type'] {
  if (key.includes('description') || key.includes('text') || key.includes('address')) return 'long';
  return 'text';
}

// sinkronkan data server -> form
watch(
  [data, group],
  () => {
    const src = (data.value ?? {}) as Record<string, unknown>;
    for (const k of Object.keys(values)) delete values[k];
    const keys: string[] = [];
    for (const [k, v] of Object.entries(src)) {
      keys.push(k);
      if (Array.isArray(v)) values[k] = v.join('\n');
      else if (v === null || v === undefined) values[k] = '';
      else if (typeof v === 'boolean' || typeof v === 'number') values[k] = v;
      else values[k] = String(v);
    }
    extraKeys.value = keys;
  },
  { immediate: true },
);

watch(group, async () => {
  error.value = '';
  saved.value = false;
  loading.value = true;
  await refetch();
  loading.value = false;
});

function switchGroup(g: string) {
  void router.push(`/admin/pengaturan/${g}`);
}

async function onSave() {
  saving.value = true;
  error.value = '';
  saved.value = false;
  try {
    const payload: Record<string, unknown> = {};
    for (const f of fields.value) {
      let v: unknown = values[f.key];
      if (f.type === 'boolean') v = Boolean(v);
      else if (f.type === 'number') v = v === '' || v === null ? null : Number(v);
      else if (f.type === 'lines') v = String(v ?? '').split('\n').map((s) => s.trim()).filter(Boolean);
      payload[f.key] = v;
    }
    await api.put(`/admin/settings/${group.value}`, payload);
    saved.value = true;
    qc.invalidateQueries({ queryKey: ['admin-settings'] });
    await refetch();
  } catch (e) {
    error.value = extractErrorMessage(e);
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 24px; box-shadow: var(--shadow-sm); }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.head h2 { font-size: 1.15rem; font-weight: 700; }
.sub { font-size: 0.8rem; }
.tabs { display: flex; gap: 4px; background: var(--bg); padding: 4px; border-radius: var(--r-pill); }
.tabs button { padding: 6px 14px; border: none; border-radius: var(--r-pill); background: transparent; font-size: 0.8rem; font-weight: 600; cursor: pointer; color: var(--muted); }
.tabs button.active { background: var(--surface); color: var(--primary); box-shadow: var(--shadow-sm); }
.form { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; }
.form label { display: flex; flex-direction: column; gap: 6px; font-size: 0.8rem; font-weight: 600; color: var(--muted); }
.form input, .form textarea { padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px; font-size: 0.85rem; color: var(--ink); font-weight: 400; font-family: var(--font-sans); }
.form input[type='number'] { font-family: var(--font-mono); }
.check { display: inline-flex; align-items: center; gap: 8px; font-weight: 500; }
.hint { font-size: 0.7rem; font-weight: 400; }
.err { grid-column: 1 / -1; color: var(--danger); font-size: 0.8rem; }
.ok { grid-column: 1 / -1; color: var(--success); font-size: 0.8rem; }
.foot { grid-column: 1 / -1; display: flex; justify-content: flex-end; }
.btn { display: inline-flex; align-items: center; font-weight: 600; border-radius: var(--r-pill); padding: 8px 14px; border: 1px solid transparent; cursor: pointer; font-size: 0.8rem; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-sm { padding: 6px 12px; font-size: 0.75rem; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.muted { color: var(--muted); }
</style>
