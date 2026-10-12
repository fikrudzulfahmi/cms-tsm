<template>
  <div class="login-wrap">
    <div class="login-card">
      <div class="logo">
        <span class="logo-box" aria-hidden="true"></span>
        <span class="logo-text">TSM<span class="dot">.admin</span></span>
      </div>
      <h1>Masuk ke panel admin</h1>
      <p class="sub">Gunakan email dan kata sandi yang diberikan Super Admin.</p>

      <form @submit.prevent="onSubmit">
        <label>
          <span>Email</span>
          <input v-model="form.email" type="email" autocomplete="username" required placeholder="admin@tsm-blitar.sch.id" />
        </label>
        <label>
          <span>Kata sandi</span>
          <input v-model="form.password" type="password" autocomplete="current-password" required placeholder="••••••••" />
        </label>
        <label v-if="needOtp">
          <span>Kode otentikasi 2FA</span>
          <input v-model="form.otp" type="text" maxlength="6" placeholder="6 digit" />
        </label>

        <p v-if="error" class="err" role="alert">{{ error }}</p>

        <button class="btn btn-primary" type="submit" :disabled="loading">
          {{ loading ? 'Memproses…' : 'Masuk' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { extractErrorMessage } from '@js/shared/api';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = reactive({ email: '', password: '', otp: '' });
const error = ref('');
const loading = ref(false);
const needOtp = ref(false);

async function onSubmit() {
  loading.value = true;
  error.value = '';
  try {
    await auth.login(form.email, form.password, form.otp || undefined);
    const redirect = (route.query.redirect as string) || '/admin';
    await router.push(redirect);
  } catch (e: unknown) {
    const msg = extractErrorMessage(e);
    if (msg.includes('dua faktor') && !needOtp.value) {
      needOtp.value = true;
      error.value = 'Masukkan kode otentikasi 2FA kamu.';
    } else {
      error.value = msg;
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.login-wrap {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
}
.login-card {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--r-lg);
  padding: 40px;
  width: min(440px, 100%);
  box-shadow: var(--shadow);
}
.logo { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
.logo-box {
  width: 34px; height: 34px; border-radius: 11px;
  background: var(--primary);
  position: relative;
}
.logo-box::after {
  content: '';
  position: absolute; inset: 8px;
  border: 3px solid #fff; border-radius: 50%;
  border-right-color: transparent;
}
.logo-text { font-weight: 800; font-size: 1.2rem; color: var(--ink); }
.logo-text .dot { color: var(--primary); }
h1 { font-size: 1.5rem; margin-bottom: 8px; }
.sub { color: var(--muted); font-size: 0.9rem; margin-bottom: 24px; }
form { display: flex; flex-direction: column; gap: 16px; }
label { display: flex; flex-direction: column; gap: 6px; font-size: 0.85rem; font-weight: 600; }
input {
  padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px;
  background: var(--surface); font-size: 0.95rem;
}
input:focus { outline: 3px solid var(--sky); outline-offset: 2px; }
.err { color: var(--danger); font-size: 0.85rem; }
.btn { width: 100%; }
</style>
