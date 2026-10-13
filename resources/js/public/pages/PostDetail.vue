<template>
  <Header :settings="settings" :menu="menu" />
  <main id="main" class="container page">
    <div v-if="isPending" class="card skeleton-main" aria-hidden="true"></div>

    <div v-else-if="isError" class="card err-card">
      <p>{{ errorMessage }}</p>
      <button class="btn btn-primary btn-sm" type="button" @click="refetch()">Coba lagi</button>
    </div>

    <template v-else-if="post">
      <RouterLink class="back" to="/berita">&larr; Kembali ke berita</RouterLink>

      <article class="article">
        <span v-if="post.category" class="chip mb">{{ post.category.name }}</span>
        <h1 class="title">{{ post.title }}</h1>
        <div class="meta mono">
          <span v-if="post.author">Ditulis {{ post.author.name }}</span>
          <span>·</span>
          <span>{{ formatDate(post.published_at) }}</span>
          <span>·</span>
          <span>{{ post.stats?.views ?? 0 }} dilihat</span>
        </div>

        <figure v-if="post.featured_image?.url" class="hero">
          <img :src="post.featured_image.url" :alt="post.featured_image.alt ?? post.title" />
        </figure>

        <div class="body" v-html="post.body_html ?? post.body"></div>

        <div class="actions">
          <button
            class="btn btn-like"
            :class="{ liked: liked }"
            type="button"
            @click="toggleLike"
          >
            <span class="mono">{{ likes }}</span> Suka
          </button>

          <a
            class="btn btn-secondary btn-sm"
            :href="shareUrl('wa')"
            target="_blank" rel="noopener"
          >WhatsApp</a>
          <a
            class="btn btn-secondary btn-sm"
            :href="shareUrl('fb')"
            target="_blank" rel="noopener"
          >Facebook</a>
          <a
            class="btn btn-secondary btn-sm"
            :href="shareUrl('x')"
            target="_blank" rel="noopener"
          >X</a>
          <a
            class="btn btn-secondary btn-sm"
            :href="shareUrl('tg')"
            target="_blank" rel="noopener"
          >Telegram</a>
          <button class="btn btn-secondary btn-sm" type="button" @click="copyLink">
            {{ copied ? 'Tersalin' : 'Salin' }}
          </button>
        </div>
      </article>

      <section class="comments card">
        <h2>Komentar ({{ approvedComments.length }})</h2>
        <div v-if="!approvedComments.length" class="muted">
          Belum ada komentar. Jadilah yang pertama.
        </div>
        <ul class="comment-list">
          <li v-for="c in approvedComments" :key="c.id" class="comment">
            <div class="c-head">
              <strong>{{ c.name }}</strong>
              <span v-if="c.is_admin_reply" class="chip admin-chip">Admin</span>
            </div>
            <p>{{ c.body }}</p>
            <span class="mono c-date">{{ formatDate(c.created_at) }}</span>
          </li>
        </ul>

        <form class="comment-form" @submit.prevent="submitComment">
          <h3>Tinggalkan komentar</h3>
          <input v-model="form.name" class="input" placeholder="Nama" required maxlength="120" />
          <textarea
            v-model="form.body"
            class="input"
            placeholder="Tulis komentar…"
            required
            maxlength="1000"
            rows="4"
          ></textarea>
          <button class="btn btn-primary btn-sm" type="submit" :disabled="submitting">
            {{ submitting ? 'Mengirim…' : 'Kirim komentar' }}
          </button>
          <p v-if="formError" class="form-err">{{ formError }}</p>
        </form>
      </section>

      <section v-if="related.length" class="related">
        <h2>Berita terkait</h2>
        <div class="grid-3">
          <PostCard v-for="r in related" :key="r.id" :post="r" />
        </div>
      </section>
    </template>

    <div v-else class="card empty">
      <h2>Berita tidak ditemukan</h2>
      <RouterLink class="btn btn-primary btn-sm" to="/berita">Lihat semua berita</RouterLink>
    </div>
  </main>
  <Footer :settings="settings" />
</template>

<script setup lang="ts">
import { SITE_MENU } from '@js/public/menu';

// satu sumber kebenaran menu -> navbar konsisten di semua halaman
const menu = SITE_MENU;
import { computed, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';
import Header from '../components/Header.vue';
import Footer from '../components/Footer.vue';
import PostCard from '../components/PostCard.vue';

const route = useRoute();
const queryClient = useQueryClient();
const slug = computed(() => String(route.params.slug));

const { data: landing } = useQuery({
  queryKey: ['landing'],
  queryFn: async () => (await api.get('/site/landing')).data?.data ?? {},
  staleTime: 5 * 60_000,
});
const settings = computed(() => landing.value?.settings ?? {});

const { data, isPending, isError, error, refetch } = useQuery({
  queryKey: ['post', slug],
  queryFn: async () => {
    const res = await api.get(`/posts/${slug.value}`);
    return res.data?.data ?? res.data;
  },
});

const post = computed(() => (data.value as Record<string, unknown> | null) ?? null);
const likes = ref(0);
const liked = ref(false);
const copied = ref(false);

const approvedComments = computed(() => {
  const list = (post.value?.comments as Array<Record<string, unknown>>) ?? [];
  return list;
});
const related = computed(() => (post.value?.related as Array<Record<string, unknown>>) ?? []);

const errorMessage = computed(() => (isError.value ? extractErrorMessage(error.value) : ''));

const fmt = new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' });
function formatDate(iso?: string) {
  if (!iso) return '';
  try { return fmt.format(new Date(iso)); } catch { return iso; }
}

function shareUrl(kind: string) {
  const u = encodeURIComponent(window.location.href);
  const t = encodeURIComponent(String(post.value?.title ?? ''));
  if (kind === 'wa') return `https://wa.me/?text=${t}%20${u}`;
  if (kind === 'fb') return `https://www.facebook.com/sharer/sharer.php?u=${u}`;
  if (kind === 'x') return `https://twitter.com/intent/tweet?text=${t}&url=${u}`;
  return `https://t.me/share/url?url=${u}&text=${t}`;
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(window.location.href);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
  } catch { /* abaikan */ }
}

async function toggleLike() {
  const next = liked.value ? 0 : 1;
  liked.value = !liked.value;
  likes.value += next === 1 ? 1 : -1;
  try {
    await api.put(`/posts/${slug.value}/reaction`, { value: next });
  } catch {
    liked.value = !liked.value;
    likes.value += next === 1 ? -1 : 1;
  }
}

const form = reactive({ name: '', body: '' });
const submitting = ref(false);
const formError = ref('');

async function submitComment() {
  submitting.value = true;
  formError.value = '';
  try {
    await api.post(`/posts/${slug.value}/comments`, {
      name: form.name,
      body: form.body,
    });
    form.body = '';
    await queryClient.invalidateQueries({ queryKey: ['post', slug] });
  } catch (e) {
    formError.value = extractErrorMessage(e);
  } finally {
    submitting.value = false;
  }
}

// ambil jumlah like awal + status like pengunjung (sinkron saat data load)
watch(
  () => post.value,
  (p) => {
    if (!p) return;
    likes.value = Number(p.stats?.likes ?? 0);
    liked.value = Number(p.viewer?.reaction ?? 0) === 1;
  },
  { immediate: true },
);
</script>

<style scoped>
.page { padding: 64px 0 120px; }
.back {
  display: inline-block; color: var(--muted); font-size: 0.88rem; margin-bottom: 22px;
  text-decoration: none;
}
.back:hover { color: var(--primary); }
.article { max-width: 760px; }
.chip.mb { margin-bottom: 16px; }
.title { font-size: clamp(1.7rem, 3.6vw, 2.5rem); margin-bottom: 14px; }
.meta { color: var(--muted); font-size: 0.82rem; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 28px; }
.hero { margin: 0 0 28px; border-radius: var(--r-md); overflow: hidden; border: 1px solid var(--line); }
.hero img { display: block; width: 100%; height: auto; aspect-ratio: 16 / 9; object-fit: cover; }
.body { font-size: 1.04rem; line-height: 1.8; color: var(--ink); }
.body :deep(h2) { margin: 30px 0 12px; }
.body :deep(p) { margin-bottom: 16px; }
.body :deep(ul), .body :deep(ol) { padding-left: 22px; margin-bottom: 16px; }

.actions { display: flex; gap: 10px; flex-wrap: wrap; margin: 36px 0 8px; }
.btn-like { background: var(--surface); border: 1px solid var(--line); color: var(--muted); }
.btn-like.liked { background: var(--primary); border-color: var(--primary); color: #fff; }

.comments { margin-top: 48px; padding: 26px 24px; max-width: 760px; }
.comment-list { list-style: none; display: flex; flex-direction: column; gap: 18px; margin: 20px 0 26px; }
.comment { border-left: 3px solid var(--line); padding-left: 16px; }
.c-head { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
.admin-chip { background: var(--primary); color: #fff; border: none; font-size: 0.68rem; padding: 2px 10px; }
.comment p { margin: 0 0 6px; }
.c-date { font-size: 0.74rem; color: var(--muted); }

.comment-form { border-top: 1px dashed var(--line); padding-top: 22px; }
.comment-form h3 { font-size: 1.05rem; margin-bottom: 14px; }
.input {
  width: 100%; padding: 11px 14px; margin-bottom: 12px;
  border: 1px solid var(--line); border-radius: var(--r-md);
  background: var(--surface); color: var(--ink); font: inherit; outline: none;
}
.input:focus { border-color: var(--sky); }
.form-err { color: #c62828; font-size: 0.85rem; margin-top: 8px; }

.related { margin-top: 56px; max-width: 100%; }
.related h2 { margin-bottom: 22px; }
.grid-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }

.skeleton-main { height: 500px; border-radius: var(--r-md); background: var(--bg); border: 1px dashed var(--line); }
.err-card { display: flex; flex-direction: column; align-items: flex-start; gap: 14px; }
.empty { text-align: center; padding: 64px 24px; }
.muted { color: var(--muted); }
</style>
