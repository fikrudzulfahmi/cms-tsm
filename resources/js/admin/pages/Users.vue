<template>
  <div class="card">
    <div class="head">
      <div>
        <h2>Pengguna</h2>
        <p class="muted sub">Kelola akun staf jurusan beserta hak aksesnya.</p>
      </div>
      <button class="btn btn-primary btn-sm" @click="openCreate">Tambah pengguna</button>
    </div>

    <table class="data-table">
      <thead>
        <tr><th>Nama</th><th>E-mail</th><th>Peran</th><th>Status</th><th>Login terakhir</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="u in rows" :key="u.id">
          <td><strong>{{ u.name }}</strong><span v-if="u.id === auth.user?.id" class="mono muted small">(kamu)</span></td>
          <td class="mono">{{ u.email }}</td>
          <td><span class="chip" :class="u.role">{{ roleLabel(u.role) }}</span></td>
          <td>
            <label class="switch">
              <input type="checkbox" :checked="u.is_active" :disabled="u.id === auth.user?.id" @change="toggleActive(u, ($event.target as HTMLInputElement).checked)" />
              <span class="track"><span class="knob"></span></span>
              <span class="state muted">{{ u.is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </label>
          </td>
          <td class="mono small">{{ u.last_login_at ? fmtTime(u.last_login_at) : 'Belum pernah' }}</td>
          <td class="acts">
            <button class="btn btn-secondary btn-sm" @click="openEdit(u)">Sunting</button>
            <button class="btn btn-ghost btn-sm" :disabled="u.id === auth.user?.id" @click="onDelete(u)">Hapus</button>
          </td>
        </tr>
        <tr v-if="rows.length === 0">
          <td colspan="6" class="muted empty">Belum ada pengguna.</td>
        </tr>
      </tbody>
    </table>

    <div v-if="showForm" class="backdrop" @click.self="closeForm">
      <div class="modal card">
        <h3>{{ form.id ? 'Sunting pengguna' : 'Pengguna baru' }}</h3>

        <label>
          <span>Nama lengkap</span>
          <input v-model="form.name" type="text" maxlength="120" />
        </label>
        <label>
          <span>E-mail</span>
          <input v-model="form.email" type="email" maxlength="160" />
        </label>
        <label>
          <span>{{ form.id ? 'Kata sandi baru (kosongkan bila tidak diubah)' : 'Kata sandi' }}</span>
          <input v-model="form.password" type="password" autocomplete="new-password" :placeholder="form.id ? 'Tidak diubah' : 'Minimal 8 karakter'" />
        </label>
        <label>
          <span>Peran</span>
          <select v-model="form.role">
            <option value="super_admin">Super Admin — akses penuh</option>
            <option value="editor">Editor — kelola semua berita &amp; moderasi</option>
            <option value="writer">Penulis — tulis berita sendiri</option>
          </select>
        </label>
        <label class="check">
          <input type="checkbox" v-model="form.is_active" />
          <span>Akun aktif (bisa masuk)</span>
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
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { useRouter } from 'vue-router';
import { api, extractErrorMessage } from '@js/shared/api';
import { useAuthStore } from '../stores/auth';

interface UserRow {
  id: number; name: string; email: string; role: string;
  is_active: boolean; last_login_at: string | null;
}

const auth = useAuthStore();
const router = useRouter();
const qc = useQueryClient();

// hanya super admin boleh mengelola pengguna
onMounted(() => {
  if (auth.role !== 'super_admin') void router.replace('/admin');
});

const { data } = useQuery<{ data: UserRow[] }>({
  queryKey: ['admin-users'],
  queryFn: async () => (await api.get('/admin/users')).data,
  enabled: computed(() => auth.role === 'super_admin'),
});

const rows = computed<UserRow[]>(() => data.value?.data ?? []);

const showForm = ref(false);
const saving = ref(false);
const error = ref('');
const form = reactive<{
  id: number | null; name: string; email: string; password: string; role: string; is_active: boolean;
}>({ id: null, name: '', email: '', password: '', role: 'writer', is_active: true });

function openCreate() {
  form.id = null; form.name = ''; form.email = ''; form.password = '';
  form.role = 'writer'; form.is_active = true; error.value = '';
  showForm.value = true;
}
function openEdit(u: UserRow) {
  form.id = u.id; form.name = u.name; form.email = u.email; form.password = '';
  form.role = u.role; form.is_active = u.is_active; error.value = '';
  showForm.value = true;
}
function closeForm() { showForm.value = false; }

async function onSave() {
  if (!form.name.trim() || !form.email.trim()) { error.value = 'Nama dan e-mail wajib diisi.'; return; }
  if (!form.id && form.password.length < 8) { error.value = 'Kata sandi minimal 8 karakter.'; return; }
  error.value = '';
  saving.value = true;
  try {
    if (form.id) {
      const payload: Record<string, unknown> = {
        name: form.name.trim(), email: form.email.trim(), role: form.role, is_active: form.is_active,
      };
      if (form.password) payload.password = form.password;
      await api.put(`/admin/users/${form.id}`, payload);
    } else {
      await api.post('/admin/users', {
        name: form.name.trim(), email: form.email.trim(),
        password: form.password, role: form.role, is_active: form.is_active,
      });
    }
    showForm.value = false;
    qc.invalidateQueries({ queryKey: ['admin-users'] });
  } catch (e) {
    error.value = extractErrorMessage(e);
  } finally {
    saving.value = false;
  }
}

async function toggleActive(u: UserRow, value: boolean) {
  try {
    await api.put(`/admin/users/${u.id}`, {
      name: u.name, email: u.email, role: u.role, is_active: value,
    });
    qc.invalidateQueries({ queryKey: ['admin-users'] });
  } catch (e) { alert(extractErrorMessage(e)); }
}

async function onDelete(u: UserRow) {
  if (!confirm(`Hapus akun "${u.name}"? Berita yang pernah ditulis tetap tersimpan tanpa penulis.`)) return;
  try {
    await api.delete(`/admin/users/${u.id}`);
    qc.invalidateQueries({ queryKey: ['admin-users'] });
  } catch (e) { alert(extractErrorMessage(e)); }
}

function fmtTime(t: string): string { return new Date(t).toLocaleString('id-ID'); }
function roleLabel(r: string): string {
  const map: Record<string, string> = { super_admin: 'Super Admin', editor: 'Editor', writer: 'Penulis' };
  return map[r] ?? r;
}
</script>

<style scoped>
.card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-md); padding: 24px; box-shadow: var(--shadow-sm); }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.head h2 { font-size: 1.15rem; font-weight: 700; }
.sub { font-size: 0.8rem; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.data-table th { text-align: left; color: var(--muted); font-size: 0.72rem; padding: 8px 0; border-bottom: 1px solid var(--line); }
.data-table td { padding: 12px 8px 12px 0; border-bottom: 1px dashed var(--line); vertical-align: middle; }
.small { font-size: 0.72rem; display: block; }
.chip { display: inline-flex; align-items: center; background: var(--bg); border: 1px solid var(--line); border-radius: var(--r-pill); padding: 3px 10px; font-size: 0.7rem; color: var(--primary); font-weight: 700; }
.chip.super_admin { background: rgba(13, 71, 161, 0.12); }
.chip.editor { background: rgba(66, 165, 245, 0.16); }
.chip.writer { background: var(--soft); color: var(--muted); }
.switch { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
.switch input { position: absolute; opacity: 0; pointer-events: none; }
.track { width: 40px; height: 22px; border-radius: var(--r-pill); background: var(--line); position: relative; transition: background 0.2s ease; display: inline-block; }
.knob { position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; transition: left 0.2s ease; }
.switch input:checked + .track { background: var(--primary); }
.switch input:checked + .track .knob { left: 21px; }
.switch input:disabled + .track { opacity: 0.5; }
.state { font-size: 0.72rem; }
.acts { display: flex; gap: 6px; justify-content: flex-end; }
.empty { padding: 24px 0; text-align: center; }
.btn { display: inline-flex; align-items: center; font-weight: 600; border-radius: var(--r-pill); padding: 8px 14px; border: 1px solid transparent; cursor: pointer; font-size: 0.8rem; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-secondary { background: var(--surface); color: var(--primary); border-color: var(--line); }
.btn-ghost { background: transparent; color: var(--danger); }
.btn-sm { padding: 6px 12px; font-size: 0.75rem; }
.btn:disabled { opacity: 0.45; cursor: not-allowed; }
.muted { color: var(--muted); }
.mono { font-family: var(--font-mono); }
.backdrop { position: fixed; inset: 0; background: rgba(11, 31, 58, 0.45); display: grid; place-items: center; padding: 20px; z-index: 40; }
.modal { width: min(480px, 100%); display: flex; flex-direction: column; gap: 14px; }
.modal h3 { font-size: 1.02rem; }
.modal label { display: flex; flex-direction: column; gap: 6px; font-size: 0.8rem; font-weight: 600; color: var(--muted); }
.modal input, .modal select { padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px; font-size: 0.85rem; color: var(--ink); font-weight: 400; }
.modal .check { flex-direction: row; align-items: center; gap: 8px; }
.modal-foot { display: flex; justify-content: flex-end; gap: 8px; }
.err { color: var(--danger); font-size: 0.78rem; }
</style>
