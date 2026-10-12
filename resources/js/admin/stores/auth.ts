import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { api } from '@js/shared/api';

interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar: string | null;
  is_active: boolean;
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AdminUser | null>(null);
  const loading = ref(false);

  const isAuthenticated = computed(() => user.value !== null);
  const role = computed(() => user.value?.role ?? '');
  const isSuperAdmin = computed(() => role.value === 'super_admin');

  async function fetchMe() {
    loading.value = true;
    try {
      const res = await api.get('/auth/me');
      user.value = res.data.data;
    } catch {
      user.value = null;
    } finally {
      loading.value = false;
    }
  }

  async function login(email: string, password: string, otp?: string) {
    const res = await api.post('/auth/login', { email, password, otp });
    user.value = res.data.data;
    return res.data;
  }

  async function logout() {
    await api.post('/auth/logout');
    user.value = null;
  }

  function reset() {
    user.value = null;
  }

  return { user, loading, isAuthenticated, role, isSuperAdmin, fetchMe, login, logout, reset };
});
