import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from './stores/auth';

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/login',
    name: 'login',
    component: () => import('./pages/Login.vue'),
    meta: { title: 'Masuk', public: true },
  },
  {
    path: '/admin',
    component: () => import('./AdminLayout.vue'),
    children: [
      { path: '', name: 'dashboard', component: () => import('./pages/Dashboard.vue'), meta: { title: 'Beranda' } },
      { path: 'berita', name: 'posts', component: () => import('./pages/Posts.vue'), meta: { title: 'Berita' } },
      { path: 'berita/baru', name: 'posts.create', component: () => import('./pages/PostEditor.vue'), meta: { title: 'Tulis berita' } },
      { path: 'berita/:id/sunting', name: 'posts.edit', component: () => import('./pages/PostEditor.vue'), meta: { title: 'Sunting berita' } },
      { path: 'berita/kategori', name: 'categories', component: () => import('./pages/Categories.vue'), meta: { title: 'Kategori' } },
      { path: 'komentar', name: 'comments', component: () => import('./pages/Comments.vue'), meta: { title: 'Komentar' } },
      { path: 'konten/:key?', name: 'sections', component: () => import('./pages/Sections.vue'), meta: { title: 'Konten situs' } },
      { path: 'media', name: 'media', component: () => import('./pages/Media.vue'), meta: { title: 'Pustaka media' } },
      { path: 'pesan', name: 'messages', component: () => import('./pages/Messages.vue'), meta: { title: 'Pesan masuk' } },
      { path: 'pengaturan/:group?', name: 'settings', component: () => import('./pages/Settings.vue'), meta: { title: 'Pengaturan' } },
      { path: 'pengguna', name: 'users', component: () => import('./pages/Users.vue'), meta: { title: 'Pengguna' } },
      { path: 'log-aktivitas', name: 'activity-logs', component: () => import('./pages/ActivityLogs.vue'), meta: { title: 'Log aktivitas' } },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/admin',
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

// guard: hanya yang login bisa akses admin
router.beforeEach(async (to) => {
  const auth = useAuthStore();
  const isPublic = to.meta.public === true;

  // state Pinia hilang saat reload; coba pulihkan dari sesi server dulu
  if (!auth.isAuthenticated && !auth.loading) {
    await auth.fetchMe();
  }

  if (!isPublic && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }
  if (isPublic && auth.isAuthenticated) {
    return { name: 'dashboard' };
  }

  const title = (to.meta.title as string) ?? 'Admin';
  document.title = `${title} — Admin TSM`;
});
