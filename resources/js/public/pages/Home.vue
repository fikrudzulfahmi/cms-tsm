<template>
  <Header :settings="settings" :menu="menu" />

  <main id="main">
    <!-- 1. Hero -->
    <section v-if="isVisible('hero')" class="section">
      <div class="container grid-2 hero">
        <div class="hero-copy">
          <div class="pill"><span class="dot"></span> Pendaftaran dibuka</div>
          <h1 class="hero-title">
            {{ hero?.title ?? 'Belajar servis sepeda motor sampai mahir' }}
          </h1>
          <p class="hero-sub">{{ hero?.subtitle }}</p>
          <div class="hero-cta">
            <a class="btn btn-primary" :href="`https://wa.me/${whatsapp}`" target="_blank" rel="noopener">
              Daftar sekarang
            </a>
            <RouterLink class="btn btn-secondary" to="/berita">Lihat kegiatan</RouterLink>
          </div>
        </div>
        <div class="hero-window">
          <WindowCard name="bengkel — praktik.xyz">
            <div class="workshop">
              <div class="row">
                <span class="mono label">UNIT</span>
                <span class="mono value">Honda Beat — Injeksi</span>
              </div>
              <div class="row">
                <span class="mono label">KELUHAN</span>
                <span class="mono value">Mesin brebet saat gas dibuka</span>
              </div>
              <div class="row">
                <span class="mono label">INSTRUKTUR</span>
                <span class="mono value">Pak Andi — 12 thn bengkel</span>
              </div>
              <div class="progress">
                <div class="progress-head">
                  <span class="mono label">PROGRES PRAKTIK</span>
                  <span class="mono value">3 / 4 selesai</span>
                </div>
                <div class="bar"><div class="bar-fill"></div></div>
              </div>
              <ul class="checklist">
                <li v-for="c in checklist" :key="c" :class="{ done: c.done }">
                  <span class="box" aria-hidden="true"></span>{{ c.text }}
                </li>
              </ul>
            </div>
          </WindowCard>
        </div>
      </div>
    </section>

    <!-- 2. Statistik -->
    <section v-if="isVisible('statistik')" class="section">
      <div class="container">
        <StatStrip :items="stats" />
      </div>
    </section>

    <!-- 3. Kompetensi -->
    <section v-if="isVisible('kompetensi')" class="section">
      <div class="container">
        <h2 class="sec-title">{{ section('kompetensi')?.title }}</h2>
        <p class="sec-sub">{{ section('kompetensi')?.subtitle }}</p>
        <div class="grid-3">
          <CompetencyCard
            v-for="(c, i) in competencies"
            :key="c.id"
            :title="c.title"
            :description="c.description"
            :highlight="i === 0"
          />
        </div>
      </div>
    </section>

    <!-- 4. Program -->
    <section v-if="isVisible('program')" class="section section-surface">
      <div class="container">
        <h2 class="sec-title">{{ section('program')?.title }}</h2>
        <p class="sec-sub">{{ section('program')?.subtitle }}</p>
        <div class="grid-3">
          <div v-for="p in programs" :key="p.id" class="card">
            <h3>{{ p.title }}</h3>
            <p>{{ p.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 5. Sarana -->
    <section v-if="isVisible('sarana')" class="section">
      <div class="container grid-2">
        <div>
          <h2 class="sec-title">{{ section('sarana')?.title }}</h2>
          <p class="sec-sub">{{ section('sarana')?.subtitle }}</p>
          <p>{{ saranaText }}</p>
        </div>
        <WindowCard name="spesifikasi-bengkel.csv">
          <table class="spec-table">
            <thead>
              <tr><th>Alat</th><th>Spek</th><th>Jumlah</th></tr>
            </thead>
            <tbody>
              <tr v-for="f in facilities" :key="f.id">
                <td>{{ f.name }}</td>
                <td class="mono">{{ f.spec || '—' }}</td>
                <td class="mono">{{ f.quantity }}</td>
              </tr>
            </tbody>
          </table>
        </WindowCard>
      </div>
    </section>

    <!-- 6. Alur belajar -->
    <section v-if="isVisible('tahap')" class="section">
      <div class="container">
        <h2 class="sec-title">{{ section('tahap')?.title }}</h2>
        <p class="sec-sub">{{ section('tahap')?.subtitle }}</p>
        <div class="grid-3">
          <div v-for="s in stages" :key="s.id" class="card">
            <span class="chip mono">{{ s.class_label }}</span>
            <h3 style="margin-top: 12px">{{ s.title }}</h3>
            <p>{{ s.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 7. Prestasi -->
    <section v-if="isVisible('prestasi')" class="section section-surface">
      <div class="container">
        <h2 class="sec-title">{{ section('prestasi')?.title }}</h2>
        <p class="sec-sub">{{ section('prestasi')?.subtitle }}</p>
        <div class="grid-3">
          <div v-for="a in achievements" :key="a.id" class="card">
            <span class="chip mono">{{ a.year }}</span>
            <h3 style="margin-top: 12px">{{ a.title }}</h3>
            <p>{{ a.student_name }} — {{ a.level }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 8. Berita -->
    <section v-if="isVisible('berita')" class="section">
      <div class="container">
        <div class="sec-head">
          <div>
            <h2 class="sec-title">{{ section('berita')?.title }}</h2>
            <p class="sec-sub">{{ section('berita')?.subtitle }}</p>
          </div>
          <RouterLink to="/berita" class="btn btn-secondary btn-sm">Lihat semua</RouterLink>
        </div>
        <div class="grid-3">
          <PostCard v-for="p in posts" :key="p.id" :post="p" />
        </div>
      </div>
    </section>

    <!-- 9. Umpan balik -->
    <section v-if="isVisible('umpan_balik')" class="section section-surface">
      <div class="container">
        <h2 class="sec-title">{{ section('umpan_balik')?.title }}</h2>
        <p class="sec-sub">{{ section('umpan_balik')?.subtitle }}</p>
        <div class="grid-3">
          <blockquote v-for="f in feedback" :key="f.id" class="card">
            <p>"{{ f.message }}"</p>
            <footer>
              <strong>{{ f.name }}</strong> · Lulus {{ f.graduation_year }} · {{ f.occupation }}
            </footer>
          </blockquote>
        </div>
      </div>
    </section>

    <!-- 10. Karier -->
    <section v-if="isVisible('karier')" class="section">
      <div class="container">
        <h2 class="sec-title">{{ section('karier')?.title }}</h2>
        <p class="sec-sub">{{ section('karier')?.subtitle }}</p>
        <div class="chip-row">
          <span v-for="c in careers" :key="c.id" class="chip">{{ c.label }}</span>
        </div>
      </div>
    </section>

    <!-- 11. Kontak -->
    <section v-if="isVisible('kontak')" class="section section-surface">
      <div class="container grid-2">
        <div>
          <h2 class="sec-title">{{ section('kontak')?.title }}</h2>
          <p class="sec-sub">{{ section('kontak')?.subtitle }}</p>
          <ul class="contact-list">
            <li><strong>Alamat</strong><br />{{ address }}</li>
            <li><strong>WhatsApp</strong><br />{{ whatsappDisplay }}</li>
            <li><strong>Email</strong><br />{{ email }}</li>
            <li><strong>Jam layanan</strong><br />{{ serviceHours }}</li>
          </ul>
          <a class="btn btn-primary" :href="`https://wa.me/${whatsapp}`" target="_blank" rel="noopener">
            Chat WhatsApp
          </a>
        </div>
        <WindowCard name="lokasi.maps">
          <iframe
            :src="mapsUrl"
            width="100%"
            height="320"
            style="border: 0; border-radius: 12px"
            loading="lazy"
            title="Peta lokasi"
          ></iframe>
        </WindowCard>
      </div>
    </section>

    <!-- 12. CTA -->
    <section v-if="isVisible('cta')" class="section">
      <div class="container">
        <div class="cta-block">
          <h2>{{ section('cta')?.title }}</h2>
          <p>{{ section('cta')?.subtitle }}</p>
          <a class="btn" :href="`https://wa.me/${whatsapp}`" target="_blank" rel="noopener">
            Daftar sekarang
          </a>
        </div>
      </div>
    </section>
  </main>

  <Footer :settings="settings" />
</template>

<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query';
import { computed } from 'vue';
import { api } from '@js/shared/api';
import WindowCard from '../components/WindowCard.vue';
import StatStrip from '../components/StatStrip.vue';
import CompetencyCard from '../components/CompetencyCard.vue';
import PostCard from '../components/PostCard.vue';
import Header from '../components/Header.vue';
import Footer from '../components/Footer.vue';

interface LandingData {
  settings: Record<string, unknown>;
  sections: Array<{ key: string; title: string | null; subtitle: string | null; sort: number; is_visible: boolean }>;
  competencies: Array<{ id: number; title: string; description: string | null }>;
  programs: Array<{ id: number; title: string; description: string | null }>;
  facilities: Array<{ id: number; name: string; spec: string | null; quantity: number }>;
  stages: Array<{ id: number; class_label: string; title: string; description: string | null }>;
  achievements: Array<{ id: number; title: string; level: string | null; year: number | null; student_name: string | null }>;
  careers: Array<{ id: number; label: string }>;
  feedback: Array<{ id: number; name: string; graduation_year: number | null; occupation: string | null; message: string }>;
  posts: Array<{ id: number; title: string; slug: string; excerpt: string | null }>;
}

const { data, suspense } = useQuery<LandingData>({
  queryKey: ['landing'],
  queryFn: async () => (await api.get('/site/landing')).data,
  staleTime: 5 * 60_000, // 5 menit (spec 5.3)
});

const settings = computed(() => data.value?.settings ?? {});
const sections = computed(() => data.value?.sections ?? []);
const competencies = computed(() => data.value?.competencies ?? []);
const programs = computed(() => data.value?.programs ?? []);
const facilities = computed(() => data.value?.facilities ?? []);
const stages = computed(() => data.value?.stages ?? []);
const achievements = computed(() => data.value?.achievements ?? []);
const careers = computed(() => data.value?.careers ?? []);
const feedback = computed(() => data.value?.feedback ?? []);
const posts = computed(() => data.value?.posts ?? []);

const hero = computed(() => sections.value.find((s) => s.key === 'hero'));
const stats = computed(() => {
  const extra = sections.value.find((s) => s.key === 'statistik')?.extra as
    | { stats?: Array<{ label: string; value: string }> }
    | undefined;
  return extra?.stats ?? [];
});

const whatsapp = computed(() => String(settings.value.school_whatsapp ?? '6281234567890'));
const whatsappDisplay = computed(() => {
  const w = whatsapp.value;
  return w.startsWith('62') ? '+' + w : w;
});
const address = computed(() => String(settings.value.school_address ?? ''));
const email = computed(() => String(settings.value.school_email ?? ''));
const serviceHours = computed(() => String(settings.value.service_hours ?? ''));
const mapsUrl = computed(() => String(settings.value.google_maps_url ?? ''));
const saranaText = computed(() => String(sections.value.find((s) => s.key === 'sarana')?.body ?? ''));
const menu = computed(() => [
  { label: 'Beranda', to: '/' },
  { label: 'Berita', to: '/berita' },
  { label: 'Prestasi', to: '/prestasi' },
  { label: 'Galeri', to: '/galeri' },
  { label: 'Tentang', to: '/tentang' },
  { label: 'Kontak', to: '/kontak' },
]);

function section(key: string) {
  return sections.value.find((s) => s.key === key);
}
function isVisible(key: string) {
  const s = section(key);
  return s ? s.is_visible : true;
}

const checklist = [
  { text: 'Periksa kelistrikan busi', done: true },
  { text: 'Bersihkan injektor', done: true },
  { text: 'Ganti filter udara', done: true },
  { text: 'Uji jalan', done: false },
];
</script>

<style scoped>
.section { padding-block: 88px; }
.section-surface { background: var(--surface); border-block: 1px solid var(--line); }
.sec-title { margin-bottom: 8px; }
.sec-sub { color: var(--muted); margin-bottom: 32px; max-width: 62ch; }
.sec-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 32px; flex-wrap: wrap; }

.hero { align-items: center; }
.hero-title { margin-block: 20px 16px; }
.hero-sub { color: var(--muted); font-size: 1.08rem; max-width: 46ch; margin-bottom: 28px; }
.hero-cta { display: flex; gap: 12px; flex-wrap: wrap; }
.hero-window { animation: float 6s ease-in-out infinite; }
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

.workshop { display: flex; flex-direction: column; gap: 14px; }
.workshop .row { display: flex; justify-content: space-between; gap: 16px; font-size: 0.9rem; }
.workshop .label { color: var(--steel); }
.progress { margin-top: 4px; }
.progress-head { display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 8px; }
.bar { height: 8px; background: var(--bg); border-radius: 99px; overflow: hidden; }
.bar-fill { height: 100%; width: 75%; border-radius: 99px; background: linear-gradient(135deg, var(--sky), var(--primary)); }
.checklist { list-style: none; display: flex; flex-direction: column; gap: 10px; margin-top: 8px; }
.checklist li { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; }
.checklist .box { width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid var(--line); flex-shrink: 0; }
.checklist li.done .box { background: var(--primary); border-color: var(--primary); }

.spec-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.spec-table th { text-align: left; color: var(--muted); font-size: 0.75rem; padding-bottom: 8px; border-bottom: 1px dashed var(--line); }
.spec-table td { padding: 10px 0; border-bottom: 1px dashed var(--line); }

.chip-row { display: flex; flex-wrap: wrap; gap: 10px; }
blockquote.card p { font-size: 1.05rem; margin-bottom: 12px; }
blockquote.card footer { color: var(--muted); font-size: 0.85rem; }

.contact-list { list-style: none; display: flex; flex-direction: column; gap: 16px; margin: 24px 0; }
.contact-list strong { color: var(--ink); }

@media (max-width: 860px) {
  .section { padding-block: 56px; }
}
@media (prefers-reduced-motion: reduce) {
  .hero-window { animation: none; }
}
</style>
