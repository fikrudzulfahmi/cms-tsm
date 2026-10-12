<template>
  <div class="card">
    <div class="head">
      <div>
        <h2>Kategori</h2>
        <p class="muted sub">Urutkan kategori sesuai tampilan di situs publik.</p>
      </div>
      <button class="btn btn-primary btn-sm" @click="openCreate">Tambah kategori</button>
    </div>

    <table class="data-table">
      <thead>
        <tr><th>Nama</th><th>Slug</th><th class="num">Jumlah berita</th><th class="num">Urutan</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="(c, i) in ordered" :key="c.id">
          <td>
            <strong>{{ c.name }}</strong>
            <span v-if="c.description" class="muted desc">{{ c.description }}</span>
          </td>
          <td class="mono">{{ c.slug }}</td>
          <td class="mono num">{{ c.posts_count ?? 0 }}</td>
          <td class="order">
            <button class="btn btn-secondary btn-sm" :disabled="i === 0" title="Naikkan" @click="move(i, -1)">↑</button>
            <button class="btn btn-secondary btn-sm" :disabled="i === ordered.length - 1" title="Turunkan" @click="move(i, 1)">↓</button>
          </td>
          <td class="acts">
            <button class="btn btn-secondary btn-sm" @click="openEdit(c)">Sunting</button>
            <button class="btn btn-ghost btn-sm" @click="onDelete(c)">Hapus</button>
          </td>
        </tr>
        <tr v-if="ordered.length === 0">
          <td colspan="5" class="muted empty">Belum ada kategori. Tambahkan kategori pertama.</td>
        </tr>
      </tbody>
    </table>

    <!-- modal tambah / sunting -->
    <div v-if="showForm" class="backdrop" @click.self="closeForm">
      <div class="modal card">
        <h3>{{ form.id ? 'Sunting kategori' : 'Kategori baru' }}</h3>
        <label>
          <span>Nama kategori</span>
          <input v-model="form.name" type="text" placeholder="mis. Prestasi" maxlength="120" />
        </label>
        <label>
          <span>Deskripsi</span>
          <textarea v-model="form.description" rows="3" placeholder="Penjelasan singkat kategori (opsional)"></textarea>
        </label>
        <p v-if="error" class="err">{{ error }}</p>
        <div class="modal-foot">
          <button class="btn btn-secondary btn-sm" @click="closeForm">Batal</button>
          <button class="btn btn-primary btn-sm" :disabled="saving" @click="onSave">
            {{ saving ? 'Menyimpan…' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api, extractErrorMessage } from '@js/shared/api';

interface CategoryRow {
  id: number; name: string; slug: string; description: string | null;
  posts_count?: number; sort?: number;
}

const qc = useQueryClient();

const { data } = useQuery<{ data: CategoryRow[] }>({
  queryKey: ['admin-categories'],
  queryFn: async () => (await api.get('/admin/categories')).data,
});

const rows = computed<CategoryRow[]>(() => data.value?.data ?? []);

const showForm = ref(false);
const saving = ref(false);
const error = ref('');
const form = reactive<{ id: number | null; name: string; description: string }>({
  id: null, name: '', description: '',
});

function openCreate() {
  form.id = null; form.name = ''; form.description = ''; error.value = '';
  showForm.value = true;
}
function openEdit(c: CategoryRow) {
  form.id = c.id; form.name = c.name; form.description = c.description ?? ''; error.value = '';
  showForm.value = true;
}
function closeForm() { showForm.value = false; }

async function onSave() {
  if (!form.name.trim()) { error.value = 'Nama kategori wajib diisi.'; return; }
  error.value = '';
  saving.value = true;
  try {
    const payload = { name: form.name.trim(), description: form.description.trim() || null };
    if (form.id) await api.put(`/admin/categories/${form.id}`, payload);
    else await api.post('/admin/categories', payload);
    showForm.value = false;
    qc.invalidateQueries({ queryKey: ['admin-categories'] });
  } catch (e) {
    error.value = extractErrorMessage(e);
  } finally {
    saving.value = false;
  }
}

async function onDelete(c: CategoryRow) {
  if (!confirm(`Hapus kategori "${c.name}"? Berita di dalamnya tidak akan terhapus.`)) return;
  try {
    await api.delete(`/admin/categories/${c.id}`);
    qc.invalidateQueries({ queryKey: ['admin-categories'] });
  } catch (e) { alert(extractErrorMessage(e)); }
}

// --- urutan (naik/turun) ---
const order = ref<number[] | null>(null);
const ordered = computed<CategoryRow[]>(() => {
  const list = [...rows.value];
  if (!order.value) return list;
  return order.value
    .map((id) => list.find((c) => c.id === id))
    .filter((c): c is CategoryRow => Boolean(c))
    .concat(list.filter((c) => !order.value!.includes(c.id)));
});

function move(index: number, delta: number) {
  const list = [...ordered.value];
  const target = index + delta;
  if (target < 0 || target >= list.length) return;
  const [item] = list.splice(index, 1);
  list.splice(target, 0, item);
  order.value = list.map((c) => c.id);
  void saveOrder();
}

async function saveOrder() {
  if (!order.value) return;
  try {
    await api.patch('/admin/categories/reorder', { ids: order.value });
    order.value = null;
    qc.invalidateQueries({ queryKey: ['admin-categories'] });
  } catch (e) {
    order.value = null;
    alert(extractErrorMessage(e));
  }
}
</script>

<style scoped>
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 24px; box-shadow: var(--shadow-sm); }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
.head h2 { font-size: 1.15rem; font-weight: 700; }
.sub { font-size: 0.8rem; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.data-table th { text-align: left; color: var(--muted); font-size: 0.72rem; padding: 8px 0; border-bottom: 1px solid var(--line); }
.data-table td { padding: 12px 0; border-bottom: 1px dashed var(--line); vertical-align: middle; }
.num { text-align: right; }
.order { text-align: right; white-space: nowrap; }
.order button { padding: 4px 10px; margin-left: 4px; }
.desc { display: block; font-size: 0.75rem; max-width: 460px; }
.empty { padding: 24px 0; text-align: center; }
.acts { display: flex; gap: 6px; justify-content: flex-end; }
.btn { display: inline-flex; align-items: center; font-weight: 600; border-radius: var(--r-pill); padding: 8px 14px; border: 1px solid transparent; cursor: pointer; font-size: 0.8rem; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-secondary { background: var(--surface); color: var(--primary); border-color: var(--line); }
.btn-ghost { background: transparent; color: var(--danger); }
.btn-sm { padding: 6px 12px; font-size: 0.75rem; }
.btn:disabled { opacity: 0.45; cursor: not-allowed; }
.muted { color: var(--muted); }
.mono { font-family: var(--font-mono); }
.backdrop { position: fixed; inset: 0; background: rgba(11, 31, 58, 0.45); display: grid; place-items: center; padding: 20px; z-index: 40; }
.modal { width: min(460px, 100%); display: flex; flex-direction: column; gap: 14px; }
.modal h3 { font-size: 1.02rem; }
.modal label { display: flex; flex-direction: column; gap: 6px; font-size: 0.8rem; font-weight: 600; color: var(--muted); }
.modal input, .modal textarea { padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px; font-size: 0.85rem; color: var(--ink); font-weight: 400; }
.modal-foot { display: flex; justify-content: flex-end; gap: 8px; }
.err { color: var(--danger); font-size: 0.78rem; }
</style>
