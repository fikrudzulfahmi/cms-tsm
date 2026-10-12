<template>
  <div class="wrap">
    <div class="card list">
      <div class="head">
        <h2>Pesan masuk</h2>
        <div class="tabs">
          <button v-for="t in tabs" :key="t.value" :class="{ active: status === t.value }" @click="status = t.value">
            {{ t.label }}
          </button>
        </div>
      </div>

      <table class="data-table">
        <thead>
          <tr><th>Pengirim</th><th>Jenis</th><th>Status</th><th>Waktu</th></tr>
        </thead>
        <tbody>
          <tr
            v-for="m in rows"
            :key="m.id"
            :class="{ picked: detail?.id === m.id }"
            @click="open(m)"
          >
            <td>
              <strong>{{ m.name }}</strong>
              <span class="mono muted small">{{ m.phone || m.email || '—' }}</span>
            </td>
            <td>
              <span v-if="m.type === 'pendaftaran'" class="chip pendaftaran">Pendaftaran siswa baru</span>
              <span v-else class="chip">{{ typeLabel(m.type) }}</span>
            </td>
            <td><span class="chip mono" :class="m.status">{{ statusLabel(m.status) }}</span></td>
            <td class="mono small">{{ fmtTime(m.created_at) }}</td>
          </tr>
          <tr v-if="rows.length === 0">
            <td colspan="4" class="muted empty">Tidak ada pesan pada status ini.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card detail" v-if="detail">
      <div class="head">
        <h2>Detail pesan</h2>
        <button class="close" @click="detail = null" title="Tutup">×</button>
      </div>

      <div v-if="detail.type === 'pendaftaran'" class="flag">
        <strong>Pendaftaran siswa baru</strong>
        <span>Calon siswa tertarik mendaftar. Segera hubungi lewat WhatsApp atau e-mail.</span>
      </div>

      <dl>
        <dt>Nama</dt><dd>{{ detail.name }}</dd>
        <dt>WhatsApp</dt>
        <dd>
          <a v-if="detail.phone" :href="`https://wa.me/${waNumber(detail.phone)}`" target="_blank" rel="noopener" class="mono">{{ detail.phone }}</a>
          <span v-else class="muted">—</span>
        </dd>
        <dt>E-mail</dt>
        <dd>
          <a v-if="detail.email" :href="`mailto:${detail.email}`" class="mono">{{ detail.email }}</a>
          <span v-else class="muted">—</span>
        </dd>
        <dt>Jenis</dt><dd>{{ typeLabel(detail.type) }}</dd>
        <dt>Waktu</dt><dd class="mono">{{ fmtTime(detail.created_at) }}</dd>
        <dt>Status</dt><dd><span class="chip mono" :class="detail.status">{{ statusLabel(detail.status) }}</span></dd>
      </dl>

      <div class="body">
        <span class="lbl">Isi pesan</span>
        <p>{{ detail.message }}</p>
      </div>

      <div class="foot">
        <button v-if="detail.status === 'baru'" class="btn btn-primary btn-sm" :disabled="busy" @click="setStatus('baca')">
          Tandai sudah dibaca
        </button>
        <button v-if="detail.status !== 'selesai'" class="btn btn-secondary btn-sm" :disabled="busy" @click="setStatus('selesai')">
          Tandai selesai ditindaklanjuti
        </button>
        <span v-if="detail.status === 'selesai'" class="muted small">Pesan sudah ditandai selesai.</span>
      </div>
    </div>

    <div class="card detail placeholder" v-else>
      <p class="muted">Pilih salah satu pesan untuk melihat isi lengkapnya.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';

interface MessageRow {
  id: number; name: string; phone: string | null; email: string | null;
  message: string; type: string; status: string; created_at: string;
}

const qc = useQueryClient();
const status = ref('baru');
const detail = ref<MessageRow | null>(null);
const busy = ref(false);

const tabs = [
  { value: 'baru', label: 'Baru' },
  { value: 'baca', label: 'Sudah dibaca' },
  { value: 'selesai', label: 'Selesai' },
];

const { data } = useQuery<{ data: MessageRow[] }>({
  queryKey: ['admin-messages', status],
  queryFn: async () => (await api.get('/admin/messages', { params: { status: status.value } })).data,
});

const rows = computed<MessageRow[]>(() => data.value?.data ?? []);
watch(status, () => { detail.value = null; });

function open(m: MessageRow) { detail.value = m; }

async function setStatus(next: 'baca' | 'selesai') {
  if (!detail.value) return;
  busy.value = true;
  try {
    await api.patch(`/admin/messages/${detail.value.id}`, { status: next });
    detail.value = { ...detail.value, status: next };
    qc.invalidateQueries({ queryKey: ['admin-messages'] });
  } catch (e) {
    alert(extractErrorMessage(e));
  } finally {
    busy.value = false;
  }
}

function fmtTime(t: string): string { return new Date(t).toLocaleString('id-ID'); }
function waNumber(phone: string): string { return phone.replace(/[^0-9]/g, '').replace(/^0/, '62'); }
function typeLabel(t: string): string {
  const map: Record<string, string> = { pendaftaran: 'Pendaftaran siswa baru', pertanyaan: 'Pertanyaan' };
  return map[t] ?? t;
}
function statusLabel(s: string): string {
  const map: Record<string, string> = { baru: 'Baru', baca: 'Dibaca', selesai: 'Selesai' };
  return map[s] ?? s;
}
</script>

<style scoped>
.wrap { display: grid; grid-template-columns: minmax(300px, 1fr) minmax(300px, 1fr); gap: 20px; align-items: start; }
@media (max-width: 960px) { .wrap { grid-template-columns: 1fr; } }
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 22px; box-shadow: var(--shadow-sm); }
.head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
.head h2 { font-size: 1.1rem; font-weight: 700; }
.tabs { display: flex; gap: 4px; background: var(--bg); padding: 4px; border-radius: var(--r-pill); }
.tabs button { padding: 6px 12px; border: none; border-radius: var(--r-pill); background: transparent; font-size: 0.76rem; font-weight: 600; cursor: pointer; color: var(--muted); }
.tabs button.active { background: var(--surface); color: var(--primary); box-shadow: var(--shadow-sm); }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
.data-table th { text-align: left; color: var(--muted); font-size: 0.7rem; padding: 8px 0; border-bottom: 1px solid var(--line); }
.data-table td { padding: 11px 0; border-bottom: 1px dashed var(--line); cursor: pointer; vertical-align: middle; }
.data-table tr.picked td { background: var(--soft); }
.small { font-size: 0.72rem; display: block; }
.chip { display: inline-flex; align-items: center; background: var(--bg); border: 1px solid var(--line); border-radius: var(--r-pill); padding: 3px 10px; font-size: 0.68rem; color: var(--primary); font-weight: 700; }
.chip.pendaftaran { background: rgba(217, 119, 6, 0.12); color: var(--warn); border-color: rgba(217, 119, 6, 0.3); }
.chip.baru { background: rgba(13, 71, 161, 0.1); }
.chip.selesai { background: rgba(22, 163, 74, 0.12); color: var(--success); border-color: rgba(22, 163, 74, 0.3); }
.empty { padding: 24px 0; text-align: center; }
.close { background: none; border: none; font-size: 1.3rem; line-height: 1; cursor: pointer; color: var(--muted); }
.flag { background: rgba(217, 119, 6, 0.1); border: 1px solid rgba(217, 119, 6, 0.3); border-radius: 12px; padding: 10px 14px; margin-bottom: 14px; display: flex; flex-direction: column; font-size: 0.8rem; }
.flag strong { color: var(--warn); }
.flag span { color: var(--muted); }
dl { display: grid; grid-template-columns: 110px 1fr; gap: 6px 12px; font-size: 0.84rem; margin-bottom: 14px; }
dt { color: var(--muted); font-size: 0.75rem; }
dd { word-break: break-word; }
.body { border-top: 1px dashed var(--line); padding-top: 12px; }
.lbl { font-size: 0.72rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
.body p { margin-top: 6px; font-size: 0.86rem; white-space: pre-wrap; }
.foot { display: flex; gap: 8px; align-items: center; margin-top: 16px; flex-wrap: wrap; }
.placeholder { display: grid; place-items: center; min-height: 160px; text-align: center; }
.btn { display: inline-flex; align-items: center; font-weight: 600; border-radius: var(--r-pill); padding: 8px 14px; border: 1px solid transparent; cursor: pointer; font-size: 0.8rem; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-secondary { background: var(--surface); color: var(--primary); border-color: var(--line); }
.btn-sm { padding: 6px 12px; font-size: 0.75rem; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.muted { color: var(--muted); }
.mono { font-family: var(--font-mono); }
</style>
