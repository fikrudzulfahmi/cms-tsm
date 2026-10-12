<template>
  <div class="dashboard">
    <!-- strip statistik -->
    <div class="stat-strip">
      <div><div class="num">{{ fmt(data?.visits.total) }}</div><div class="label">Total kunjungan</div></div>
      <div><div class="num">{{ fmt(data?.visits.unique_visitors) }}</div><div class="label">Pengunjung unik</div></div>
      <div><div class="num">{{ fmt(data?.visits.pageviews) }}</div><div class="label">Halaman dilihat</div></div>
      <div><div class="num">{{ data?.content.posts_published ?? 0 }}</div><div class="label">Berita terbit</div></div>
    </div>

    <div class="grid-2 gap">
      <!-- grafik kunjungan (window) -->
      <div class="window">
        <div class="window-bar">
          <span class="dot" aria-hidden="true"></span><span class="dot" aria-hidden="true"></span><span class="dot" aria-hidden="true"></span>
          <span class="name mono">kunjungan-harian.chart</span>
        </div>
        <div class="window-body">
          <Line v-if="chartData" :data="chartData" :options="chartOptions" />
          <p v-else class="muted">Belum ada data kunjungan.</p>
        </div>
      </div>

      <!-- menunggu moderasi -->
      <div class="card">
        <h3>Menunggu kamu</h3>
        <ul class="todo">
          <li>
            <RouterLink to="/admin/komentar">
              <span class="num">{{ data?.pending.comments ?? 0 }}</span> komentar menunggu moderasi
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/admin/konten/umpan-balik">
              <span class="num">{{ data?.pending.feedback ?? 0 }}</span> umpan balik lulusan baru
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/admin/pesan">
              <span class="num">{{ data?.pending.messages ?? 0 }}</span> pesan masuk baru
            </RouterLink>
          </li>
        </ul>
        <div v-if="data?.alerts.low_seo_posts" class="alert">
          <strong>⚠ {{ data.alerts.low_seo_posts }} berita</strong> punya skor SEO di bawah 60. Perbaiki agar lebih mudah ditemukan.
        </div>
      </div>
    </div>

    <div class="grid-2 gap">
      <!-- berita terpopuler -->
      <div class="card">
        <h3>Berita terpopuler</h3>
        <table class="data-table">
          <thead>
            <tr><th>Berita</th><th class="num">Dilihat</th><th class="num">Suka</th><th class="num">Komentar</th></tr>
          </thead>
          <tbody>
            <tr v-for="p in data?.popular_posts" :key="p.id">
              <td><RouterLink :to="`/admin/berita/${p.id}/sunting`">{{ p.title }}</RouterLink></td>
              <td class="mono num">{{ fmt(p.views_count) }}</td>
              <td class="mono num">{{ p.likes_count }}</td>
              <td class="mono num">{{ p.comments_count }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- sumber trafik -->
      <div class="card">
        <h3>Sumber trafik</h3>
        <table class="data-table">
          <thead><tr><th>Sumber</th><th class="num">Kunjungan</th></tr></thead>
          <tbody>
            <tr v-for="s in data?.traffic.sources" :key="s.source">
              <td class="mono">{{ sourceLabel(s.source) }}</td>
              <td class="mono num">{{ fmt(s.total) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS, CategoryScale, LinearScale, PointElement,
  LineElement, Title, Tooltip, Legend,
} from 'chart.js';
import { api } from '@js/shared/api';
import { RouterLink } from 'vue-router';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

interface DashboardData {
  range: string;
  visits: { total: number; unique_visitors: number; pageviews: number; chart: Array<{ date: string; visits: number; unique_visitors: number }> };
  content: { posts_published: number; posts_draft: number; posts_scheduled: number; achievements: number; competencies: number; gallery_photos: number };
  popular_posts: Array<{ id: number; title: string; views_count: number; likes_count: number; comments_count: number }>;
  pending: { comments: number; feedback: number; messages: number };
  traffic: { sources: Array<{ source: string; total: number }>; devices: Array<{ device: string; total: number }> };
  alerts: { low_seo_posts: number; queue_failed: number };
}

const { data, suspense } = useQuery<DashboardData>({
  queryKey: ['admin-dashboard', '30d'],
  queryFn: async () => (await api.get('/admin/dashboard', { params: { range: '30d' } })).data.data,
  // tidak pakai await suspense(): render kerangka dulu, data mengisi setelah load
});

const chartData = computed(() => {
  const chart = data.value?.visits.chart ?? [];
  if (chart.length === 0) return null;
  return {
    labels: chart.map((c) => c.date),
    datasets: [
      {
        label: 'Kunjungan',
        data: chart.map((c) => c.visits),
        borderColor: '#0D47A1',
        backgroundColor: 'rgba(13, 71, 161, 0.1)',
        tension: 0.3,
        fill: true,
      },
    ],
  };
});

const chartOptions = {
  responsive: true,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true } },
};

function fmt(n: number | undefined | null): string {
  if (n === null || n === undefined) return '0';
  return n.toLocaleString('id-ID');
}
function sourceLabel(s: string): string {
  const map: Record<string, string> = { direct: 'Langsung', search: 'Pencarian', social: 'Media sosial', referral: 'Rujukan' };
  return map[s] ?? s;
}
</script>

<style scoped>
.dashboard { display: flex; flex-direction: column; gap: 24px; }
.gap { gap: 24px; }
.stat-strip {
  display: grid; grid-template-columns: repeat(4, 1fr);
  background: var(--surface); border: 1px solid var(--line);
  border-radius: var(--r-md); overflow: hidden;
}
.stat-strip > div { padding: 20px 24px; border-right: 1px solid var(--line); }
.stat-strip > div:last-child { border-right: none; }
.stat-strip .num { font-size: 1.75rem; font-weight: 800; color: var(--primary); font-family: var(--font-mono); }
.stat-strip .label { font-size: 0.8rem; color: var(--muted); }

.window {
  background: var(--surface); border: 1px solid var(--line);
  border-radius: var(--r-lg); overflow: hidden; box-shadow: var(--shadow-sm);
}
.window-bar { display: flex; align-items: center; gap: 8px; background: var(--soft); padding: 10px 16px; border-bottom: 1px solid var(--line); }
.window-bar .dot { width: 10px; height: 10px; border-radius: 50%; background: #B5CDE8; }
.window-bar .name { font-size: 0.8rem; color: var(--muted); margin-left: 8px; }
.window-body { padding: 20px; }

.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 24px; box-shadow: var(--shadow-sm); }
.card h3 { margin-bottom: 16px; }

.todo { list-style: none; display: flex; flex-direction: column; gap: 12px; }
.todo a { display: flex; align-items: center; gap: 8px; color: var(--ink); }
.todo a:hover { text-decoration: none; color: var(--primary); }
.todo .num { font-family: var(--font-mono); font-weight: 800; color: var(--primary); min-width: 28px; }
.alert {
  margin-top: 16px; padding: 12px 16px; border-radius: 12px;
  background: var(--soft); border: 1px solid var(--line); font-size: 0.85rem;
}

.data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.data-table th { text-align: left; color: var(--muted); font-size: 0.72rem; padding: 8px 0; border-bottom: 1px solid var(--line); }
.data-table td { padding: 10px 0; border-bottom: 1px dashed var(--line); }
.data-table .num { text-align: right; }
.mono { font-family: var(--font-mono); }

@media (max-width: 860px) {
  .stat-strip { grid-template-columns: 1fr 1fr; }
  .grid-2 { grid-template-columns: 1fr !important; }
}
</style>
