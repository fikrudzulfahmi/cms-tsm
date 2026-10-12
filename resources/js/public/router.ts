import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  { path: '/', name: 'home', component: () => import('./pages/Home.vue'), meta: { title: 'Beranda' } },
  {
    path: '/berita',
    name: 'posts',
    component: () => import('./pages/Posts.vue'),
    meta: { title: 'Berita' },
  },
  {
    path: '/berita/kategori/:slug',
    name: 'posts.category',
    component: () => import('./pages/Posts.vue'),
    meta: { title: 'Kategori' },
  },
  {
    path: '/berita/:slug',
    name: 'posts.show',
    component: () => import('./pages/PostDetail.vue'),
    meta: { title: 'Berita' },
  },
  {
    path: '/prestasi',
    name: 'achievements',
    component: () => import('./pages/Achievements.vue'),
    meta: { title: 'Prestasi' },
  },
  {
    path: '/galeri',
    name: 'gallery',
    component: () => import('./pages/Gallery.vue'),
    meta: { title: 'Galeri' },
  },
  {
    path: '/tentang',
    name: 'about',
    component: () => import('./pages/About.vue'),
    meta: { title: 'Tentang' },
  },
  {
    path: '/kontak',
    name: 'contact',
    component: () => import('./pages/Contact.vue'),
    meta: { title: 'Kontak' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('./pages/NotFound.vue'),
    meta: { title: 'Halaman tidak ditemukan' },
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) return savedPosition;
    return { top: 0, behavior: 'smooth' };
  },
});

// judul halaman otomatis + tracking kunjungan
router.afterEach((to) => {
  const title = (to.meta.title as string) ?? '';
  document.title = title ? `${title} — TSM Blitar` : 'TSM Blitar';

  // POST /track via sendBeacon (spec 5.3)
  if ('sendBeacon' in navigator) {
    const blob = new Blob(
      [JSON.stringify({ path: to.fullPath, referrer: document.referrer })],
      { type: 'application/json' }
    );
    navigator.sendBeacon('/api/v1/track', blob);
  }
});
