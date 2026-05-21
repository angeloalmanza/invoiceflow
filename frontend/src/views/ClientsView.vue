<script setup>
import { ref, onMounted } from 'vue'
import { Plus, Pencil, Trash2, X, Building2 } from 'lucide-vue-next'
import api from '@/api/client'

const clients = ref([])
const loading = ref(true)
const showModal = ref(false)
const saving = ref(false)
const editingId = ref(null)
const error = ref('')

const form = ref({ name: '', email: '', phone: '', address: '', city: '', country: '', vat_number: '' })

onMounted(fetchClients)

async function fetchClients() {
  loading.value = true
  const { data } = await api.get('/clients')
  clients.value = data
  loading.value = false
}

function openNew() {
  editingId.value = null
  form.value = { name: '', email: '', phone: '', address: '', city: '', country: '', vat_number: '' }
  error.value = ''
  showModal.value = true
}

function openEdit(c) {
  editingId.value = c.id
  form.value = { name: c.name, email: c.email || '', phone: c.phone || '', address: c.address || '', city: c.city || '', country: c.country || '', vat_number: c.vat_number || '' }
  error.value = ''
  showModal.value = true
}

async function save() {
  error.value = ''
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/clients/${editingId.value}`, form.value)
    } else {
      await api.post('/clients', form.value)
    }
    showModal.value = false
    await fetchClients()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : 'Errore nel salvataggio'
  } finally {
    saving.value = false
  }
}

async function remove(id) {
  if (!confirm('Eliminare questo cliente?')) return
  await api.delete(`/clients/${id}`)
  clients.value = clients.value.filter(c => c.id !== id)
}

function initials(name) {
  return name?.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase() || '?'
}
</script>

<template>
  <div class="page">

    <div class="page-header">
      <div>
        <h1 class="page-title">Clienti</h1>
        <p class="page-subtitle">{{ clients.length }} clienti totali</p>
      </div>
      <button class="btn-primary" @click="openNew">
        <Plus class="btn-icon" /> Nuovo cliente
      </button>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="client-list">
      <div v-for="i in 4" :key="i" class="skeleton-card"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!clients.length" class="empty-state">
      <div class="empty-icon-wrap">
        <Building2 class="empty-icon" />
      </div>
      <p class="empty-title">Nessun cliente ancora</p>
      <p class="empty-sub">Aggiungi il tuo primo cliente per iniziare a fatturare</p>
      <button class="btn-primary" style="margin-top:1.25rem" @click="openNew">
        <Plus class="btn-icon" /> Nuovo cliente
      </button>
    </div>

    <!-- Client list: card con avatar iniziali — identità visiva immediata -->
    <div v-else class="client-list">
      <div v-for="c in clients" :key="c.id" class="client-card">
        <!--
          Avatar con iniziali: pattern noto (Gmail, Slack) che associa
          un'identità visiva al cliente senza richiedere foto.
        -->
        <div class="client-avatar">{{ initials(c.name) }}</div>
        <div class="client-info">
          <span class="client-name">{{ c.name }}</span>
          <span class="client-meta">
            {{ c.email || '—' }}
            <template v-if="c.city"> · {{ c.city }}</template>
          </span>
        </div>
        <!-- Invoice count badge: info utile senza navigare -->
        <span class="invoice-count">
          {{ c.invoices_count ?? 0 }} {{ (c.invoices_count ?? 0) === 1 ? 'fattura' : 'fatture' }}
        </span>
        <div class="client-actions">
          <button class="action-btn" title="Modifica" @click="openEdit(c)">
            <Pencil class="action-icon" />
          </button>
          <button class="action-btn action-btn--danger" title="Elimina" @click="remove(c.id)">
            <Trash2 class="action-icon" />
          </button>
        </div>
      </div>
    </div>

  </div>

  <!-- Modal: Teleport per z-index corretto, overlay scuro -->
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal">
          <div class="modal-header">
            <h2 class="modal-title">{{ editingId ? 'Modifica cliente' : 'Nuovo cliente' }}</h2>
            <button class="modal-close" @click="showModal = false"><X class="close-icon" /></button>
          </div>

          <form @submit.prevent="save" class="modal-body">
            <div class="field">
              <label class="field-label">Nome <span class="required">*</span></label>
              <input v-model="form.name" required type="text" class="field-input" placeholder="Es. Mario Rossi" />
            </div>
            <div class="field-row">
              <div class="field">
                <label class="field-label">Email</label>
                <input v-model="form.email" type="email" class="field-input" placeholder="email@esempio.com" />
              </div>
              <div class="field">
                <label class="field-label">Telefono</label>
                <input v-model="form.phone" type="tel" class="field-input" placeholder="+39 000 000 0000" />
              </div>
            </div>
            <div class="field">
              <label class="field-label">Indirizzo</label>
              <input v-model="form.address" type="text" class="field-input" placeholder="Via Roma 1" />
            </div>
            <div class="field-row">
              <div class="field">
                <label class="field-label">Città</label>
                <input v-model="form.city" type="text" class="field-input" placeholder="Milano" />
              </div>
              <div class="field">
                <label class="field-label">Paese</label>
                <input v-model="form.country" type="text" class="field-input" placeholder="Italia" />
              </div>
            </div>
            <div class="field">
              <label class="field-label">Partita IVA</label>
              <input v-model="form.vat_number" type="text" class="field-input" placeholder="IT12345678901" />
            </div>

            <p v-if="error" class="error-msg">{{ error }}</p>

            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showModal = false">Annulla</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Salvataggio...' : 'Salva' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.page { padding: 2.5rem; }
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #111827; letter-spacing: -0.025em; }
.page-subtitle { font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; }

/* Skeleton */
.client-list { display: flex; flex-direction: column; gap: 0.625rem; }
.skeleton-card {
  height: 4.5rem; background: #fff; border: 1px solid #e5e7eb;
  border-radius: 0.875rem; animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }

/* Empty state */
.empty-state {
  background: #fff; border: 1px solid #e5e7eb; border-radius: 0.875rem;
  padding: 4rem 2rem; text-align: center;
}
.empty-icon-wrap {
  width: 3.5rem; height: 3.5rem; border-radius: 0.875rem;
  background: #f3f4f6; display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem;
}
.empty-icon { width: 1.5rem; height: 1.5rem; color: #9ca3af; }
.empty-title { font-size: 0.9375rem; font-weight: 600; color: #111827; }
.empty-sub { font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; }

/* Client card: flex orizzontale con 4 zone ben distanziate */
.client-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 0.875rem;
  padding: 1rem 1.25rem;
  transition: border-color 0.15s;
}
.client-card:hover { border-color: #c7d2fe; }

/* Avatar: colore indigo coerente col brand, iniziali al posto della foto */
.client-avatar {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  background: rgba(79, 70, 229, 0.1);
  color: #4f46e5;
  font-size: 0.8125rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  letter-spacing: 0.03em;
}

.client-info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.client-name { font-size: 0.9375rem; font-weight: 600; color: #111827; }
.client-meta { font-size: 0.8125rem; color: #6b7280; margin-top: 0.125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Invoice count badge: info contestuale secondaria */
.invoice-count {
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  background: #f3f4f6;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  white-space: nowrap;
}

.client-actions { display: flex; gap: 0.25rem; }
.action-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 2rem; height: 2rem;
  border-radius: 0.5rem; border: none; background: transparent;
  color: #9ca3af; cursor: pointer; transition: background 0.15s, color 0.15s;
}
.action-btn:hover { background: #f3f4f6; color: #374151; }
.action-btn--danger:hover { background: #fef2f2; color: #ef4444; }
.action-icon { width: 0.9375rem; height: 0.9375rem; }

/* Modal overlay: semi-trasparente per mantenere il contesto visivo */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 50; padding: 1rem;
  backdrop-filter: blur(2px);
}
.modal {
  background: #fff;
  border-radius: 1rem;
  width: 100%; max-width: 28rem;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
  overflow: hidden;
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f3f4f6;
}
.modal-title { font-size: 1rem; font-weight: 700; color: #111827; }
.modal-close {
  width: 2rem; height: 2rem; border-radius: 0.5rem;
  border: none; background: transparent; color: #9ca3af;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background 0.15s;
}
.modal-close:hover { background: #f3f4f6; color: #374151; }
.close-icon { width: 1.125rem; height: 1.125rem; }

.modal-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.modal-footer { display: flex; gap: 0.75rem; margin-top: 0.5rem; }

/* Fields */
.field { display: flex; flex-direction: column; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem; }
.field-label { font-size: 0.8125rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem; }
.required { color: #4f46e5; }

/* Input: stesso stile del form di login — coerenza nel design system */
.field-input {
  padding: 0.625rem 0.875rem;
  border: 1.5px solid #d1d5db;
  border-radius: 0.625rem;
  font-size: 0.875rem;
  color: #111827;
  background: #fff;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.field-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
}
.field-input::placeholder { color: #9ca3af; }

.error-msg {
  font-size: 0.8125rem; color: #dc2626;
  background: #fef2f2; border: 1px solid #fecaca;
  border-radius: 0.625rem; padding: 0.625rem 0.875rem;
}

/* Buttons */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.625rem 1.125rem;
  background: #4f46e5; color: #fff;
  font-size: 0.875rem; font-weight: 600;
  border: none; border-radius: 0.625rem;
  cursor: pointer; transition: background 0.15s;
  flex: 1; justify-content: center;
}
.btn-primary:hover { background: #4338ca; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-secondary {
  display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
  padding: 0.625rem 1.125rem;
  background: #fff; color: #374151;
  font-size: 0.875rem; font-weight: 500;
  border: 1.5px solid #e5e7eb; border-radius: 0.625rem;
  cursor: pointer; transition: background 0.15s;
  flex: 1;
}
.btn-secondary:hover { background: #f9fafb; }
.btn-icon { width: 1rem; height: 1rem; }

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-active .modal, .modal-leave-active .modal { transition: transform 0.2s, opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform: scale(0.96) translateY(8px); opacity: 0; }
</style>
