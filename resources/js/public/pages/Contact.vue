<template>
  <Header :settings="settings" :menu="menu" />
  <main id="main" class="container page">
    <div class="page-head">
      <span class="postcard mono">KONTAK</span>
      <h1>Hubungi Kami</h1>
      <p class="muted">Ada pertanyaan? Silakan hubungi atau kunjungi kami.</p>
    </div>

    <div class="grid-2">
      <div class="contact-info">
        <div class="card info-item">
          <span class="mono label">ALAMAT</span>
          <p>{{ settings.school_address || '—' }}</p>
        </div>
        <div class="card info-item">
          <span class="mono label">WHATSAPP</span>
          <p>
            <a class="link" :href="`https://wa.me/${settings.school_whatsapp}`" target="_blank" rel="noopener">
              {{ whatsappDisplay }}
            </a>
          </p>
        </div>
        <div class="card info-item">
          <span class="mono label">EMAIL</span>
          <p>
            <a class="link" :href="`mailto:${settings.school_email}`">
              {{ settings.school_email || '—' }}
            </a>
          </p>
        </div>
        <div class="card info-item">
          <span class="mono label">JAM LAYANAN</span>
          <p>{{ settings.service_hours || '—' }}</p>
        </div>
      </div>

      <form class="card form" @submit.prevent="submit">
        <h2>Kirim pesan</h2>
        <input v-model="form.name" class="input" placeholder="Nama" required maxlength="120" />
        <input v-model="form.phone" class="input" placeholder="Nomor WhatsApp" maxlength="30" />
        <input v-model="form.email" class="input" type="email" placeholder="Email" maxlength="160" />
        <textarea
          v-model="form.message"
          class="input"
          placeholder="Pesan kamu…"
          required
          maxlength="2000"
          rows="5"
        ></textarea>
        <button class="btn btn-primary" type="submit" :disabled="submitting">
          {{ submitting ? 'Mengirim…' : 'Kirim pesan' }}
        </button>
        <p v-if="success" class="ok">Pesan terkirim. Terima kasih!</p>
        <p v-if="formError" class="form-err">{{ formError }}</p>
      </form>
    </div>

    <section v-if="mapsUrl" class="map-section">
      <h2 class="sec-title">Lokasi</h2>
      <div class="map-wrap card">
        <iframe
          :src="mapsUrl"
          title="Peta lokasi sekolah"
          width="100%"
          height="360"
          style="border: 0; border-radius: var(--r-md);"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
        ></iframe>
      </div>
    </section>
  </main>
  <Footer :settings="settings" />
</template>

<script setup lang="ts">
import { SITE_MENU } from '@js/public/menu';

// satu sumber kebenaran menu -> navbar konsisten di semua halaman
const menu = SITE_MENU;
import { computed, reactive, ref } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';
import Header from '../components/Header.vue';
import Footer from '../components/Footer.vue';

const { data: landing } = useQuery({
  queryKey: ['landing'],
  queryFn: async () => (await api.get('/site/landing')).data?.data ?? {},
  staleTime: 5 * 60_000,
});

const settings = computed(() => landing.value?.settings ?? {} as Record<string, string>);

const whatsappDisplay = computed(() => {
  const w = String(settings.value.school_whatsapp ?? '');
  return w.startsWith('62') ? '+' + w : w;
});
const mapsUrl = computed(() => String(settings.value.google_maps_url ?? ''));

const form = reactive({ name: '', phone: '', email: '', message: '' });
const submitting = ref(false);
const success = ref(false);
const formError = ref('');

async function submit() {
  submitting.value = true;
  formError.value = '';
  success.value = false;
  try {
    await api.post('/contact', {
      name: form.name,
      phone: form.phone,
      email: form.email,
      message: form.message,
      type: 'pertanyaan',
    });
    success.value = true;
    form.name = '';
    form.phone = '';
    form.email = '';
    form.message = '';
  } catch (e) {
    formError.value = extractErrorMessage(e);
  } finally {
    submitting.value = false;
  }
}
</script>

<style scoped>
.page { padding: 72px 0 120px; }
.page-head { margin-bottom: 32px; }
.postcard {
  display: inline-block; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em;
  background: var(--bg); border: 1px dashed var(--line); color: var(--primary);
  border-radius: var(--r-pill); padding: 4px 12px; margin-bottom: 16px;
}
h1 { font-size: clamp(1.8rem, 4vw, 2.6rem); }
.muted { color: var(--muted); }

.grid-2 {
  display: grid; grid-template-columns: 0.9fr 1.1fr; gap: 32px;
  align-items: start;
}
.contact-info { display: flex; flex-direction: column; gap: 16px; }
.info-item { padding: 20px 22px; }
.info-item .label {
  display: block; font-size: 0.68rem; color: var(--primary);
  letter-spacing: 0.08em; margin-bottom: 8px;
}
.info-item p { margin: 0; }
.link { color: var(--primary); text-decoration: none; }
.link:hover { text-decoration: underline; }

.form { padding: 28px 26px; }
.form h2 { margin-bottom: 18px; }
.input {
  width: 100%; padding: 11px 14px; margin-bottom: 12px;
  border: 1px solid var(--line); border-radius: var(--r-md);
  background: var(--surface); color: var(--ink); font: inherit; outline: none;
}
.input:focus { border-color: var(--sky); }
.ok { color: var(--primary); font-weight: 600; margin-top: 10px; }
.form-err { color: #c62828; font-size: 0.85rem; margin-top: 8px; }

.map-section { margin-top: 56px; }
.sec-title { margin-bottom: 22px; }
.map-wrap { padding: 10px; overflow: hidden; }

@media (max-width: 860px) {
  .grid-2 { grid-template-columns: 1fr; }
}
</style>
