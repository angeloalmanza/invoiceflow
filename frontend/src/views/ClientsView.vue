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
</script>

<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Clienti</h1>
        <p class="text-gray-500 text-sm mt-1">{{ clients.length }} clienti totali</p>
      </div>
      <button @click="openNew"
        class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
        <Plus class="w-4 h-4" /> Nuovo cliente
      </button>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="bg-white rounded-xl border border-gray-200 p-5 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-40 mb-2"></div>
        <div class="h-3 bg-gray-200 rounded w-56"></div>
      </div>
    </div>

    <div v-else-if="!clients.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
      <Building2 class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500">Nessun cliente ancora. Creane uno!</p>
    </div>

    <div v-else class="space-y-3">
      <div v-for="c in clients" :key="c.id"
        class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between">
        <div>
          <p class="font-semibold text-gray-900">{{ c.name }}</p>
          <p class="text-sm text-gray-500">{{ c.email || '—' }} <span v-if="c.city">· {{ c.city }}</span></p>
          <p class="text-xs text-gray-400 mt-1">{{ c.invoices_count }} {{ c.invoices_count === 1 ? 'fattura' : 'fatture' }}</p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="openEdit(c)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500 transition-colors">
            <Pencil class="w-4 h-4" />
          </button>
          <button @click="remove(c.id)" class="p-2 hover:bg-red-50 rounded-lg text-gray-500 hover:text-red-500 transition-colors">
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <Teleport to="body">
    <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h2 class="text-base font-semibold">{{ editingId ? 'Modifica cliente' : 'Nuovo cliente' }}</h2>
          <button @click="showModal = false" class="p-1 hover:bg-gray-100 rounded-lg"><X class="w-5 h-5" /></button>
        </div>
        <form @submit.prevent="save" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
            <input v-model="form.name" required type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input v-model="form.email" type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
              <input v-model="form.phone" type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
            <input v-model="form.address" type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
              <input v-model="form.city" type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Paese</label>
              <input v-model="form.country" type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
            <input v-model="form.vat_number" type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>

          <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showModal = false"
              class="flex-1 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
              Annulla
            </button>
            <button type="submit" :disabled="saving"
              class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors">
              {{ saving ? 'Salvataggio...' : 'Salva' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
