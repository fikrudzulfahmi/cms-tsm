<template>
  <div class="card">
    <div class="head">
      <h2>Moderasi komentar</h2>
      <div class="tabs">
        <button v-for="t in tabs" :key="t.value" :class="{ active: status === t.value }" @click="status = t.value">
          {{ t.label }}
        </button>
      </div>
    </div>

    <div class="bulk" v-if="selected.length">
      <span class="mono">{{ selected.length }} dipilih</span>
      <button class="btn btn-secondary btn-sm" @click="bulk('disetujui')">Setujui</button>
      <button class="btn btn-secondary btn-sm" @click="bulk('ditolak')">Tolak</button>
      <button class="btn btn-secondary btn-sm" @click="bulk('spam')">Tandai spam</button>
      <button class="btn btn-ghost btn-sm" @click="bulk('hapus')">Hapus</button>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th class="pick"><input type="checkbox" :checked="allSelected" @change="toggleAll" /></th>
          <th>Pengirim</th><th>Komentar</th><th>Berita</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="c in rows" :key="c.id">
          <td class="pick"><input type="checkbox" :value="c.id" v-model="selected" /></td>
          <td>{{ c.name }}</td>
          <td class="body">{{ c.body }}</td>
          <td class="muted post">{{ c.post?.title ?? '—' }}</td>
          <td><span class="chip mono" :class="c.status">{{ statusLabel(c.status) }}</span></td>
          <td class="acts">
            <button v-if="c.status !== 'disetujui'" class="btn btn-secondary btn-sm" @click="setStatus(c, 'disetujui')">Setujui</button>
            <button v-if="c.status !== 'ditolak'" class="btn btn-secondary btn-sm" @click="setStatus(c, 'ditolak')">Tolak</button>
            <button class="btn btn-secondary btn-sm" @click="openReply(c)">Balas</button>
            <button class="btn btn-ghost btn-sm" @click="onDelete(c)">Hapus</button>
          </td>
        </tr>
        <tr v-if="rows.length === 0">
          <td colspan="6" class="muted empty">Tidak ada komentar pada kategori ini.</td>
        </tr>
      </tbody>
    </table>

    <!-- modal balasan admin -->
    <div v-if="replyTo" class="backdrop" @click.self="replyTo = null">
      <div class="modal card">
        <h3>Balas komentar</h3>
        <p class="muted quote">“{{ replyTo.body }}” — <strong>{{ replyTo.name }}</strong></p>
        <textarea v-model="replyBody" rows="4" placeholder="Tulis balasan resmi jurusan…"></textarea>
        <p v-if="error" class="err">{{ error }}</p>
        <div class="modal-foot">
          <button class="btn btn-secondary btn-sm" @click="replyTo = null">Batal</button>
          <button class="btn btn-primary btn-sm" :disabled="sending" @click="sendReply">
            {{ sending ? 'Mengirim…' : 'Kirim balasan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';

interface CommentRow {
  id: number; name: string; body: string; status: string;
  post: { title: string } | null;
}

const qc = useQueryClient();
const status = ref('menunggu');
const selected = ref<number[]>([]);
const replyTo = ref<CommentRow | null>(null);
const replyBody = ref('');
const sending = ref(false);
const error = ref('');

const tabs = [
  { value: 'menunggu', label: 'Menunggu' },
  { value: 'disetujui', label: 'Disetujui' },
  { value: 'ditolak', label: 'Ditolak' },
  { value: 'spam', label: 'Spam' },
];

const { data } = useQuery<{ data: CommentRow[] }>({
  queryKey: ['admin-comments', status],
  queryFn: async () => (await api.get('/admin/comments', { params: { status: status.value } })).data,
});

const rows = computed<CommentRow[]>(() => data.value?.data ?? []);
const allSelected = computed(() => rows.value.length > 0 && selected.value.length === rows.value.length);

watch(status, () => { selected.value = []; });

function toggleAll(e: Event) {
  const checked = (e.target as HTMLInputElement).checked;
  selected.value = checked ? rows.value.map((c) => c.id) : [];
}
function refresh() { qc.invalidateQueries({ queryKey: ['admin-comments'] }); }

async function setStatus(c: CommentRow, next: string) {
  try {
    await api.patch(`/admin/comments/${c.id}`, { status: next });
    refresh();
  } catch (e) { alert(extractErrorMessage(e)); }
}

async function bulk(action: string) {
  if (selected.value.length === 0) return;
  const label: Record<string, string> = {
    disetujui: 'menyetujui', ditolak: 'menolak', spam: 'menandai spam', hapus: 'menghapus',
  };
  if (!confirm(`Yakin ${label[action] ?? action} ${selected.value.length} komentar?`)) return;
  try {
    await api.post('/admin/comments/bulk', { ids: selected.value, action });
    selected.value = [];
    refresh();
  } catch (e) { alert(extractErrorMessage(e)); }
}

function openReply(c: CommentRow) {
  replyTo.value = c; replyBody.value = ''; error.value = '';
}
async function sendReply() {
  if (!replyTo.value) return;
  if (!replyBody.value.trim()) { error.value = 'Balasan tidak boleh kosong.'; return; }
  error.value = '';
  sending.value = true;
  try {
    await api.post(`/admin/comments/${replyTo.value.id}/reply`, { body: replyBody.value.trim() });
    replyTo.value = null;
    replyBody.value = '';
    refresh();
  } catch (e) {
    error.value = extractErrorMessage(e);
  } finally {
    sending.value = false;
  }
}

async function onDelete(c: CommentRow) {
  if (!confirm('Hapus komentar ini secara permanen?')) return;
  try {
    await api.delete(`/admin/comments/${c.id}`);
    refresh();
  } catch (e) { alert(extractErrorMessage(e)); }
}

function statusLabel(s: string): string {
  const map: Record<string, string> = {
    menunggu: 'Menunggu', disetujui: 'Disetujui', ditolak: 'Ditolak', spam: 'Spam',
  };
  return map[s] ?? s;
}
</script>

<style scoped>
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 24px; box-shadow: var(--shadow-sm); }
.head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.head h2 { font-size: 1.15rem; font-weight: 700; }
.tabs { display: flex; gap: 4px; background: var(--bg); padding: 4px; border-radius: var(--r-pill); }
.tabs button { padding: 6px 14px; border: none; border-radius: var(--r-pill); background: transparent; font-size: 0.8rem; font-weight: 600; cursor: pointer; color: var(--muted); }
.tabs button.active { background: var(--surface); color: var(--primary); box-shadow: var(--shadow-sm); }
.bulk { display: flex; align-items: center; gap: 8px; background: var(--soft); border: 1px dashed var(--line); border-radius: 12px; padding: 10px 14px; margin-bottom: 14px; font-size: 0.8rem; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.data-table th { text-align: left; color: var(--muted); font-size: 0.72rem; padding: 8px 0; border-bottom: 1px solid var(--line); }
.data-table td { padding: 12px 8px 12px 0; border-bottom: 1px dashed var(--line); vertical-align: top; }
.pick { width: 34px; text-align: center; }
.body { max-width: 380px; }
.post { max-width: 220px; font-size: 0.8rem; }
.acts { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
.chip { display: inline-flex; align-items: center; background: var(--bg); border: 1px solid var(--line); border-radius: var(--r-pill); padding: 3px 10px; font-size: 0.7rem; color: var(--primary); font-weight: 700; }
.chip.disetujui { background: rgba(22, 163, 74, 0.12); color: var(--success); border-color: rgba(22, 163, 74, 0.3); }
.chip.ditolak { background: rgba(220, 38, 38, 0.1); color: var(--danger); border-color: rgba(220, 38, 38, 0.3); }
.chip.spam { background: rgba(217, 119, 6, 0.12); color: var(--warn); border-color: rgba(217, 119, 6, 0.3); }
.empty { padding: 24px 0; text-align: center; }
.btn { display: inline-flex; align-items: center; font-weight: 600; border-radius: var(--r-pill); padding: 8px 14px; border: 1px solid transparent; cursor: pointer; font-size: 0.8rem; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-secondary { background: var(--surface); color: var(--primary); border-color: var(--line); }
.btn-ghost { background: transparent; color: var(--danger); }
.btn-sm { padding: 6px 12px; font-size: 0.75rem; }
.muted { color: var(--muted); }
.mono { font-family: var(--font-mono); }
.backdrop { position: fixed; inset: 0; background: rgba(11, 31, 58, 0.45); display: grid; place-items: center; padding: 20px; z-index: 40; }
.modal { width: min(520px, 100%); display: flex; flex-direction: column; gap: 14px; }
.modal h3 { font-size: 1.02rem; }
.quote { font-size: 0.82rem; font-style: italic; background: var(--soft); border-left: 3px solid var(--line); padding: 8px 12px; border-radius: 8px; }
.modal textarea { padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px; font-size: 0.85rem; }
.modal-foot { display: flex; justify-content: flex-end; gap: 8px; }
.err { color: var(--danger); font-size: 0.78rem; }
</style>
