<template>
  <div class="card">
    <div class="head">
      <h2>Log aktivitas &amp; deteksi serangan</h2>
      <div class="tabs">
        <button :class="{ active: tab === 'all' }" @click="tab = 'all'">Semua</button>
        <button :class="{ active: tab === 'security' }" @click="tab = 'security'">Serangan terdeteksi</button>
      </div>
    </div>

    <div v-if="tab === 'security' && summary" class="summary">
      <div class="sum-card"><span class="num">{{ summary.banned_24h }}</span><span class="lbl">IP diblokir (24 jam)</span></div>
      <div class="sum-card"><span class="num">{{ summary.failed_logins_24h }}</span><span class="lbl">Gagal login (24 jam)</span></div>
      <div class="sum-card" v-for="t in summary.threats_by_type" :key="t.threat_type">
        <span class="num">{{ t.total }}</span><span class="lbl">{{ threatLabel(t.threat_type) }}</span>
      </div>
    </div>

    <table class="data-table">
      <thead>
        <tr><th>Waktu</th><th>Aksi</th><th>Pengguna</th><th>IP</th><th>Detail</th><th v-if="tab === 'security'"></th></tr>
      </thead>
      <tbody>
        <tr v-for="log in logs" :key="log.id">
          <td class="mono">{{ fmtTime(log.created_at) }}</td>
          <td><span class="chip mono" :class="{ danger: log.action.startsWith('security.') }">{{ log.action }}</span></td>
          <td>{{ log.user_name ?? '—' }}</td>
          <td class="mono">{{ log.ip ?? '—' }}</td>
          <td class="detail">{{ formatMeta(log.meta) }}</td>
          <td v-if="tab === 'security'">
            <button v-if="log.action === 'security.ip_banned'" class="btn btn-secondary btn-sm" @click="onUnban(log)">
              Bebaskan
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="bannedIps.length" class="banned">
      <h3>IP saat ini diblokir</h3>
      <div class="chip-row">
        <span v-for="ip in bannedIps" :key="ip" class="chip danger mono">
          {{ ip }}
          <button class="unban" @click="onUnbanIp(ip)">×</button>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';

const qc = useQueryClient();
const tab = ref<'all' | 'security'>('security');

interface LogRow {
  id: number; action: string; user_name: string | null; ip: string | null;
  meta: Record<string, unknown> | null; created_at: string;
}
interface SecuritySummary {
  threats_by_type: Array<{ threat_type: string; total: number }>;
  banned_24h: number; failed_logins_24h: number; banned_ips: string[];
}

const url = computed(() => tab.value === 'security' ? '/admin/activity-logs/security' : '/admin/activity-logs');
const { data } = useQuery<{ data: LogRow[]; summary?: SecuritySummary }>({
  queryKey: ['activity-logs', tab],
  queryFn: async () => (await api.get(url.value)).data,
});

const logs = computed(() => data.value?.data ?? []);
const summary = computed(() => data.value?.summary);
const bannedIps = computed(() => data.value?.summary?.banned_ips ?? []);

watch(tab, () => qc.invalidateQueries({ queryKey: ['activity-logs'] }));

async function onUnban(log: LogRow) {
  const ip = (log.meta as { ip?: string } | null)?.ip;
  if (ip) await onUnbanIp(ip);
}
async function onUnbanIp(ip: string) {
  if (!confirm(`Bebaskan IP ${ip} dari blokir?`)) return;
  try {
    await api.post('/admin/activity-logs/1/unban', { ip });
    qc.invalidateQueries({ queryKey: ['activity-logs'] });
  } catch (e) { alert(extractErrorMessage(e)); }
}
function fmtTime(t: string): string { return new Date(t).toLocaleString('id-ID'); }
function formatMeta(meta: Record<string, unknown> | null): string {
  if (!meta) return '—';
  const parts = Object.entries(meta).slice(0, 3).map(([k, v]) => `${k}: ${String(v).slice(0, 60)}`);
  return parts.join(' · ');
}
function threatLabel(t: string): string {
  const map: Record<string, string> = {
    sql_injection: 'SQL injection', xss: 'XSS', path_traversal: 'Path traversal',
    command_injection: 'Command injection', laravel_exploit: 'Eksploit framework', lfi: 'LFI',
  };
  return map[t] ?? t;
}
</script>

<style scoped>
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 24px; box-shadow: var(--shadow-sm); }
.head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 16px; flex-wrap: wrap; }
.tabs { display: flex; gap: 4px; background: var(--bg); padding: 4px; border-radius: var(--r-pill); }
.tabs button { padding: 6px 14px; border: none; border-radius: var(--r-pill); background: transparent; font-size: 0.8rem; font-weight: 600; cursor: pointer; color: var(--muted); }
.tabs button.active { background: var(--surface); color: var(--primary); box-shadow: var(--shadow-sm); }
.summary { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.sum-card { background: var(--soft); border: 1px solid var(--line); border-radius: var(--r-md); padding: 14px 18px; display: flex; flex-direction: column; }
.sum-card .num { font-size: 1.5rem; font-weight: 800; color: var(--primary); font-family: var(--font-mono); }
.sum-card .lbl { font-size: 0.75rem; color: var(--muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.data-table th { text-align: left; color: var(--muted); font-size: 0.7rem; padding: 8px 0; border-bottom: 1px solid var(--line); }
.data-table td { padding: 10px 0; border-bottom: 1px dashed var(--line); vertical-align: top; }
.detail { max-width: 320px; color: var(--muted); font-size: 0.78rem; word-break: break-word; }
.chip { display: inline-flex; align-items: center; background: var(--bg); border: 1px solid var(--line); border-radius: var(--r-pill); padding: 3px 10px; font-size: 0.7rem; color: var(--primary); font-weight: 700; }
.chip.danger { background: rgba(220, 38, 38, 0.1); color: var(--danger); border-color: rgba(220, 38, 38, 0.3); }
.banned { margin-top: 24px; border-top: 1px dashed var(--line); padding-top: 16px; }
.banned h3 { font-size: 0.95rem; margin-bottom: 12px; }
.chip-row { display: flex; gap: 8px; flex-wrap: wrap; }
.unban { background: none; border: none; color: inherit; font-size: 1rem; cursor: pointer; padding: 0 2px; }
.btn { display: inline-flex; font-weight: 600; border-radius: var(--r-pill); padding: 6px 12px; border: 1px solid var(--line); background: var(--surface); color: var(--primary); font-size: 0.75rem; cursor: pointer; }
.mono { font-family: var(--font-mono); }
</style>
