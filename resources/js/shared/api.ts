import axios, { type AxiosInstance } from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL ?? '/api/v1';

export const api: AxiosInstance = axios.create({
  baseURL,
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
});

// sertakan XSRF token dari cookie di setiap request
api.interceptors.request.use((config) => {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  if (match) {
    config.headers['X-XSRF-TOKEN'] = decodeURIComponent(match[1]);
  }
  return config;
});

// global error extractor (pesan Bahasa Indonesia dari API)
export function extractErrorMessage(error: unknown): string {
  if (error === null || typeof error !== 'object') return 'Terjadi kesalahan. Coba lagi.';
  const e = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } }; message?: string };
  if (e.response?.data?.errors) {
    const first = Object.values(e.response.data.errors)[0];
    if (Array.isArray(first) && first.length > 0) return first[0];
  }
  if (e.response?.data?.message) return e.response.data.message;
  if (e.message) return e.message;
  return 'Terjadi kesalahan. Coba lagi.';
}
