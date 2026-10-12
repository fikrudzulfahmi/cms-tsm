<template>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="brand">
        <span class="logo-box" aria-hidden="true"></span>
        <span class="logo-text">TSM<span class="dot">.admin</span></span>
      </div>

      <nav aria-label="Menu admin">
        <ul>
          <li v-for="item in visibleMenu" :key="item.to">
            <RouterLink :to="item.to" active-class="active">
              <component :is="item.icon" :size="18" aria-hidden="true" />
              <span>{{ item.label }}</span>
            </RouterLink>
          </li>
        </ul>
      </nav>

      <div class="sidebar-foot">
        <div class="user">
          <div class="avatar" aria-hidden="true">{{ initials }}</div>
          <div class="user-meta">
            <strong>{{ auth.user?.name }}</strong>
            <span class="mono">{{ roleLabel }}</span>
          </div>
        </div>
        <button class="btn btn-secondary btn-sm" @click="onLogout">Keluar</button>
      </div>
    </aside>

    <div class="main">
      <header class="topbar">
        <h1>{{ pageTitle }}</h1>
      </header>
      <main class="content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, type Component } from 'vue';
import { useRoute, useRouter, RouterView, RouterLink } from 'vue-router';
import {
  LayoutDashboard, Newspaper, MessageSquare, FileText, Image,
  Inbox, Settings, Users, ScrollText, FolderTree,
} from 'lucide-vue-next';
import { useAuthStore } from './stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

onMounted(() => {
  if (!auth.isAuthenticated) auth.fetchMe();
});

const menu: Array<{ to: string; label: string; icon: Component; roles?: string[] }> = [
  { to: '/admin', label: 'Beranda', icon: LayoutDashboard },
  { to: '/admin/berita', label: 'Berita', icon: Newspaper },
  { to: '/admin/berita/kategori', label: 'Kategori', icon: FolderTree },
  { to: '/admin/komentar', label: 'Komentar', icon: MessageSquare },
  { to: '/admin/konten', label: 'Konten situs', icon: FileText },
  { to: '/admin/media', label: 'Pustaka media', icon: Image },
  { to: '/admin/pesan', label: 'Pesan masuk', icon: Inbox },
  { to: '/admin/pengaturan', label: 'Pengaturan', icon: Settings },
  { to: '/admin/pengguna', label: 'Pengguna', icon: Users, roles: ['super_admin'] },
  { to: '/admin/log-aktivitas', label: 'Log aktivitas', icon: ScrollText, roles: ['super_admin'] },
];

const visibleMenu = computed(() =>
  menu.filter((m) => !m.roles || m.roles.includes(auth.role))
);

const pageTitle = computed(() => (route.meta.title as string) ?? 'Admin');
const initials = computed(() =>
  (auth.user?.name ?? 'A')
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0])
    .join('')
    .toUpperCase()
);
const roleLabel = computed(() => {
  const r = auth.role;
  return r === 'super_admin' ? 'Super Admin' : r === 'editor' ? 'Editor' : 'Penulis';
});

async function onLogout() {
  await auth.logout();
  auth.reset();
  await router.push('/admin/login');
}
</script>

<style scoped>
.admin-layout { display: flex; min-height: 100vh; }
.sidebar {
  width: 248px; flex-shrink: 0; background: var(--surface);
  border-right: 1px solid var(--line);
  display: flex; flex-direction: column; padding: 20px 16px;
}
.brand { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; padding: 0 8px; }
.logo-box {
  width: 34px; height: 34px; border-radius: 11px;
  background: var(--primary); position: relative;
}
.logo-box::after {
  content: ''; position: absolute; inset: 8px;
  border: 3px solid #fff; border-radius: 50%; border-right-color: transparent;
}
.logo-text { font-weight: 800; font-size: 1.1rem; }
.logo-text .dot { color: var(--primary); }

nav ul { list-style: none; display: flex; flex-direction: column; gap: 4px; flex: 1; }
nav a {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; border-radius: 12px;
  color: var(--ink); font-size: 0.9rem; font-weight: 600;
  transition: all 0.2s ease;
}
nav a:hover { background: var(--soft); text-decoration: none; }
nav a.active { background: var(--bg); color: var(--primary); }

.sidebar-foot { border-top: 1px solid var(--line); padding-top: 16px; }
.user { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--primary); color: #fff;
  display: grid; place-items: center; font-weight: 700; font-size: 0.85rem;
}
.user-meta { display: flex; flex-direction: column; font-size: 0.85rem; line-height: 1.3; }
.user-meta span { color: var(--muted); font-size: 0.75rem; }

.main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.topbar {
  background: var(--surface); border-bottom: 1px solid var(--line);
  padding: 18px 24px;
}
.topbar h1 { font-size: 1.25rem; }
.content { padding: 24px; flex: 1; }

@media (max-width: 860px) {
  .admin-layout { flex-direction: column; }
  .sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--line); }
  nav ul { flex-direction: row; flex-wrap: wrap; }
}
</style>
