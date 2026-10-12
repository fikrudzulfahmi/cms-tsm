<template>
  <article class="post-card">
    <RouterLink :to="`/berita/${post.slug}`" class="thumb">
      <img v-if="post.featured_image?.url" :src="post.featured_image.url" :alt="post.featured_image.alt || post.title" loading="lazy" />
      <div v-else class="thumb-fallback" aria-hidden="true">
        <span class="logo-dot"></span>
      </div>
    </RouterLink>

    <div class="body">
      <div class="top-row">
        <RouterLink v-if="post.category" :to="`/berita/kategori/${post.category.slug}`" class="chip">
          {{ post.category.name }}
        </RouterLink>
        <span class="mono date">{{ fmtDate(post.published_at) }}</span>
      </div>

      <h3>
        <RouterLink :to="`/berita/${post.slug}`">{{ post.title }}</RouterLink>
      </h3>

      <p class="excerpt">{{ post.excerpt }}</p>

      <div class="meta">
        <span v-if="post.author" class="author">
          <span class="avatar" aria-hidden="true">{{ initials(post.author.name) }}</span>
          {{ post.author.name }}
        </span>
        <span class="mono stat">👁 {{ fmt(post.views_count) }}</span>
        <span class="mono stat">💬 {{ post.comments_count }}</span>
        <span class="mono stat">★ {{ post.stars ?? 0 }}</span>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';

interface PostCardData {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  featured_image: { url: string; alt: string | null } | null;
  category: { name: string; slug: string } | null;
  author: { name: string } | null;
  published_at: string | null;
  views_count: number;
  comments_count: number;
  stars?: number | null;
}

defineProps<{ post: PostCardData }>();

function fmtDate(d: string | null): string {
  if (!d) return '';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
function fmt(n: number | undefined | null): string {
  return (n ?? 0).toLocaleString('id-ID');
}
function initials(name: string): string {
  return name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0])
    .join('')
    .toUpperCase();
}
</script>

<style scoped>
.post-card {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--r-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.post-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
.thumb { display: block; aspect-ratio: 16 / 10; background: var(--bg); overflow: hidden; }
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.thumb-fallback {
  width: 100%; height: 100%;
  display: grid; place-items: center;
  background: linear-gradient(135deg, var(--bg), var(--soft));
}
.logo-dot {
  width: 52px; height: 52px; border-radius: 50%;
  border: 5px solid var(--line); border-right-color: transparent;
}
.body { padding: 20px; display: flex; flex-direction: column; gap: 12px; flex: 1; }
.top-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.chip {
  display: inline-flex; align-items: center;
  background: var(--bg); border: 1px solid var(--line);
  border-radius: var(--r-pill); padding: 3px 12px;
  font-size: 0.72rem; color: var(--primary); font-weight: 700;
}
.chip:hover { background: var(--soft); }
.date { font-size: 0.72rem; color: var(--muted); }
h3 { font-size: 1.02rem; line-height: 1.35; }
h3 a { color: var(--ink); }
h3 a:hover { color: var(--primary); text-decoration: none; }
.excerpt { font-size: 0.86rem; color: var(--muted); flex: 1; }
.meta {
  display: flex; align-items: center; gap: 14px;
  border-top: 1px dashed var(--line); padding-top: 12px;
  flex-wrap: wrap;
}
.author { display: flex; align-items: center; gap: 7px; font-size: 0.78rem; font-weight: 600; }
.avatar {
  width: 24px; height: 24px; border-radius: 50%;
  background: var(--primary); color: #fff;
  display: grid; place-items: center;
  font-size: 0.62rem; font-weight: 800;
}
.stat { font-size: 0.74rem; color: var(--muted); }
.mono { font-family: var(--font-mono); }
</style>
