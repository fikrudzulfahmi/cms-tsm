<template>
  <div class="editor">
    <div class="editor-main">
      <div class="card">
        <div class="editor-head">
          <h2 v-if="!isEdit">Tulis berita baru</h2>
          <h2 v-else>Sunting berita</h2>
          <span v-if="form.status" class="chip mono">{{ statusLabel(form.status) }}</span>
        </div>

        <form @submit.prevent="onSave">
          <label class="field">
            <span>Judul <span class="req">*</span></span>
            <input v-model="form.title" type="text" required maxlength="200" placeholder="Judul menjelaskan isi berita" />
          </label>

          <label class="field">
            <span>Ringkasan</span>
            <textarea v-model="form.excerpt" rows="2" placeholder="Satu atau dua kalimat singkat untuk meta description"></textarea>
          </label>

          <div class="field">
            <span>Isi berita <span class="req">*</span></span>
            <div class="tiptap-wrap">
              <div class="tiptap-bar">
                <button type="button" @click="exec('bold')"><strong>B</strong></button>
                <button type="button" @click="exec('italic')"><em>I</em></button>
                <button type="button" @click="exec('underline')"><u>U</u></button>
                <button type="button" @click="exec('h2')">H2</button>
                <button type="button" @click="exec('h3')">H3</button>
                <button type="button" @click="exec('bulletList')">• Daftar</button>
                <button type="button" @click="exec('link')">Tautan</button>
                <button type="button" @click="exec('image')">Gambar</button>
              </div>
              <div ref="editorEl" class="tiptap-body" contenteditable="true" @input="onBodyInput"></div>
            </div>
          </div>

          <div class="grid-2">
            <label class="field">
              <span>Kategori</span>
              <select v-model="form.category_id">
                <option :value="null">— pilih —</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </label>
            <label class="field">
              <span>Tag (pisahkan koma)</span>
              <input v-model="tagsText" type="text" placeholder="lks, juara, blitar" />
            </label>
          </div>

          <div class="grid-2">
            <label class="field">
              <span>Gambar utama</span>
              <select v-model="form.featured_media_id">
                <option :value="null">— pilih dari pustaka —</option>
                <option v-for="m in media" :key="m.id" :value="m.id">{{ m.alt || m.path }}</option>
              </select>
            </label>
            <label class="field">
              <span>Alt gambar utama</span>
              <input v-model="form.featured_alt" type="text" placeholder="Deskripsi gambar untuk aksesibilitas" />
            </label>
          </div>

          <label class="field">
            <span>Boleh dikomentari?</span>
            <select v-model="form.allow_comments">
              <option :value="true">Ya</option>
              <option :value="false">Tidak</option>
            </select>
          </label>

          <div class="actions">
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Menyimpan…' : 'Simpan draf' }}
            </button>
            <button type="button" class="btn btn-secondary" @click="onPublish" :disabled="saving">
              Terbitkan
            </button>
            <button type="button" class="btn btn-ghost" @click="onAnalyze">
              Analisis SEO
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- PANEL SEO -->
    <aside class="card seo-panel">
      <h3>Analisis SEO</h3>
      <label class="field">
        <span>Kata kunci fokus <span class="req">*</span></span>
        <input v-model="seoInput.focus_keyword" type="text" placeholder="contoh: juara lks blitar" @input="scheduleAnalyze" />
      </label>
      <label class="field">
        <span>Judul SEO</span>
        <input v-model="seoInput.seo_title" type="text" maxlength="200" @input="scheduleAnalyze" />
      </label>
      <label class="field">
        <span>Meta description</span>
        <textarea v-model="seoInput.seo_description" rows="2" maxlength="300" @input="scheduleAnalyze"></textarea>
      </label>

      <div v-if="seoResult" class="seo-result">
        <div class="score-ring" :class="scoreClass">
          <span class="score-num">{{ seoResult.score }}</span>
          <span class="score-lbl">/100</span>
        </div>
        <ul class="seo-checks">
          <li v-for="(c, i) in seoResult.checks" :key="i" :class="c.status">
            <span class="ico" aria-hidden="true">{{ statusIcon(c.status) }}</span>
            <span>
              <strong>{{ c.label }}</strong>
              <em>{{ c.text }}</em>
            </span>
          </li>
        </ul>
      </div>
      <p v-else class="muted">Isi kata kunci fokus dan klik "Analisis SEO" — atau ketik, analisis jalan otomatis (jeda 600ms).</p>
    </aside>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';

const route = useRoute();
const router = useRouter();
const qc = useQueryClient();
const postId = computed(() => (route.params.id ? Number(route.params.id) : null));
const isEdit = computed(() => postId.value !== null);

interface Category { id: number; name: string; slug: string }
interface MediaItem { id: number; path: string; alt: string | null }

const { data: categories } = useQuery<Category[]>({
  queryKey: ['admin-categories'],
  queryFn: async () => (await api.get('/admin/categories')).data.data,
});
const { data: media } = useQuery<MediaItem[]>({
  queryKey: ['admin-media-lite'],
  queryFn: async () => (await api.get('/admin/media', { params: { per_page: 50 } })).data.data,
});

const form = reactive({
  title: '', slug: '', excerpt: '', body: '',
  category_id: null as number | null,
  featured_media_id: null as number | null,
  featured_alt: '',
  og_media_id: null as number | null,
  status: 'draf' as string,
  allow_comments: true,
});
const tagsText = ref('');
const seoInput = reactive({
  focus_keyword: '', seo_title: '', seo_description: '',
});
const seoResult = ref<{ score: number; checks: Array<{ group: string; label: string; status: string; text: string }> } | null>(null);
const saving = ref(false);
const editorEl = ref<HTMLElement | null>(null);
let analyzeTimer: ReturnType<typeof setTimeout> | null = null;
let saveTimer: ReturnType<typeof setTimeout> | null = null;

onMounted(async () => {
  if (isEdit.value && postId.value) {
    try {
      const res = await api.get(`/admin/posts/${postId.value}`);
      const p = res.data.data;
      form.title = p.title;
      form.slug = p.slug;
      form.excerpt = p.excerpt ?? '';
      form.body = p.body_html ?? p.body ?? '';
      form.category_id = p.category?.id ?? null;
      form.featured_media_id = p.featured_media_id ?? null;
      form.status = p.status;
      form.allow_comments = p.allow_comments;
      tagsText.value = (p.tags ?? []).join(', ');
      seoInput.focus_keyword = p.focus_keyword ?? '';
      seoInput.seo_title = p.seo_title ?? '';
      seoInput.seo_description = p.seo_description ?? '';
      if (editorEl.value) editorEl.value.innerHTML = form.body;
    } catch (e) {
      alert(extractErrorMessage(e));
    }
  }
});

function onBodyInput() {
  if (editorEl.value) form.body = editorEl.value.innerHTML;
  scheduleAutoSave();
}

function exec(command: string) {
  if (command === 'link') {
    const url = prompt('Masukkan URL tautan:');
    if (url) document.execCommand('createLink', false, url);
  } else if (command === 'image') {
    const url = prompt('Masukkan URL gambar:');
    if (url) document.execCommand('insertImage', false, url);
  } else if (command === 'h2' || command === 'h3') {
    document.execCommand('formatBlock', false, command);
  } else {
    document.execCommand(command, false);
  }
  onBodyInput();
}

function scheduleAutoSave() {
  if (saveTimer) clearTimeout(saveTimer);
  saveTimer = setTimeout(() => {
    if (isEdit.value && form.title) void save(true);
  }, 30_000); // autosave 30s (spec 7.1)
}

async function save(silent = false) {
  saving.value = true;
  try {
    const payload = {
      ...form,
      tags: tagsText.value.split(',').map((t) => t.trim()).filter(Boolean),
    };
    if (isEdit.value && postId.value) {
      await api.put(`/admin/posts/${postId.value}`, payload);
    } else {
      const res = await api.post('/admin/posts', payload);
      await router.replace(`/admin/berita/${res.data.data.id}/sunting`);
    }
    if (!silent) qc.invalidateQueries({ queryKey: ['admin-posts'] });
  } catch (e) {
    alert(extractErrorMessage(e));
  } finally {
    saving.value = false;
  }
}

async function onSave() { await save(); }
async function onPublish() {
  await save();
  if (postId.value) {
    try {
      await api.post(`/admin/posts/${postId.value}/publish`);
      qc.invalidateQueries({ queryKey: ['admin-posts'] });
      alert('Berita diterbitkan.');
    } catch (e) {
      alert(extractErrorMessage(e));
    }
  }
}

function scheduleAnalyze() {
  if (analyzeTimer) clearTimeout(analyzeTimer);
  analyzeTimer = setTimeout(() => void onAnalyze(), 600); // debounce 600ms (spec 7.2)
}

async function onAnalyze() {
  try {
    const res = await api.post('/admin/seo/analyze', {
      id: postId.value,
      title: form.title,
      slug: form.slug,
      body: form.body,
      excerpt: form.excerpt,
      focus_keyword: seoInput.focus_keyword,
      seo_title: seoInput.seo_title,
      seo_description: seoInput.seo_description,
      featured_media_id: form.featured_media_id,
      featured_alt: form.featured_alt,
      og_media_id: form.og_media_id,
    });
    seoResult.value = res.data.data;
  } catch (e) {
    // diam-diam; skor SEO bukan blocker penulisan
    console.warn(extractErrorMessage(e));
  }
}

function statusLabel(s: string): string {
  const map: Record<string, string> = { draf: 'Draf', menunggu_review: 'Menunggu review', terjadwal: 'Terjadwal', terbit: 'Terbit', arsip: 'Arsip' };
  return map[s] ?? s;
}
function statusIcon(s: string): string {
  return s === 'ok' ? '✓' : s === 'error' ? '✗' : s === 'warning' ? '!' : '·';
}
const scoreClass = computed(() => {
  const s = seoResult.value?.score ?? 0;
  return s >= 80 ? 'good' : s >= 50 ? 'mid' : 'low';
});

watch(() => form.title, (t) => {
  if (!isEdit.value) form.slug = t.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 75);
});
</script>

<style scoped>
.editor { display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start; }
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 24px; box-shadow: var(--shadow-sm); }
.editor-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
form { display: flex; flex-direction: column; gap: 16px; }
.field { display: flex; flex-direction: column; gap: 6px; font-size: 0.85rem; font-weight: 600; }
.field input, .field textarea, .field select {
  padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px;
  font-weight: 400; font-size: 0.95rem; background: var(--surface);
}
.req { color: var(--danger); }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.tiptap-wrap { border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
.tiptap-bar { display: flex; gap: 4px; padding: 8px; background: var(--soft); border-bottom: 1px solid var(--line); flex-wrap: wrap; }
.tiptap-bar button {
  padding: 6px 10px; border: 1px solid var(--line); border-radius: 8px;
  background: var(--surface); font-size: 0.8rem; cursor: pointer;
}
.tiptap-bar button:hover { background: var(--bg); }
.tiptap-body {
  min-height: 300px; padding: 16px; outline: none; font-size: 0.95rem; line-height: 1.7;
}
.tiptap-body :deep(h2) { font-size: 1.4rem; margin: 16px 0 8px; }
.tiptap-body :deep(h3) { font-size: 1.15rem; margin: 12px 0 6px; }
.tiptap-body :deep(p) { margin-bottom: 8px; }
.tiptap-body :deep(ul) { padding-left: 20px; margin-bottom: 8px; }

.actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
.btn { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; border-radius: var(--r-pill); padding: 10px 20px; border: 1px solid transparent; cursor: pointer; font-size: 0.9rem; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-d); }
.btn-secondary { background: var(--surface); color: var(--primary); border-color: var(--line); }
.btn-ghost { background: transparent; color: var(--primary); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }

.seo-panel { position: sticky; top: 24px; max-height: calc(100vh - 48px); overflow-y: auto; }
.seo-result { margin-top: 16px; border-top: 1px dashed var(--line); padding-top: 16px; }
.score-ring {
  display: flex; align-items: baseline; gap: 4px; margin-bottom: 16px;
  width: fit-content; padding: 8px 16px; border-radius: var(--r-pill);
}
.score-ring.good { background: rgba(22, 163, 74, 0.1); color: var(--success); }
.score-ring.mid { background: rgba(217, 119, 6, 0.1); color: var(--warn); }
.score-ring.low { background: rgba(220, 38, 38, 0.1); color: var(--danger); }
.score-num { font-size: 1.75rem; font-weight: 800; font-family: var(--font-mono); }
.score-lbl { font-size: 0.8rem; color: var(--muted); }
.seo-checks { list-style: none; display: flex; flex-direction: column; gap: 10px; }
.seo-checks li { display: flex; gap: 10px; font-size: 0.8rem; line-height: 1.4; }
.seo-checks .ico { flex-shrink: 0; width: 18px; height: 18px; border-radius: 50%; display: grid; place-items: center; font-size: 0.7rem; font-weight: 800; margin-top: 1px; }
.seo-checks li.ok .ico { background: var(--success); color: #fff; }
.seo-checks li.error .ico { background: var(--danger); color: #fff; }
.seo-checks li.warning .ico { background: var(--warn); color: #fff; }
.seo-checks li.suggestion .ico { background: var(--sky); color: #fff; }
.seo-checks em { display: block; color: var(--muted); font-style: normal; font-weight: 400; }
.muted { color: var(--muted); font-size: 0.85rem; }
.chip { display: inline-flex; align-items: center; background: var(--bg); border: 1px solid var(--line); border-radius: var(--r-pill); padding: 4px 12px; font-size: 0.75rem; color: var(--primary); font-weight: 600; }
.mono { font-family: var(--font-mono); }

@media (max-width: 960px) {
  .editor { grid-template-columns: 1fr; }
  .seo-panel { position: static; max-height: none; }
  .grid-2 { grid-template-columns: 1fr; }
}
</style>
