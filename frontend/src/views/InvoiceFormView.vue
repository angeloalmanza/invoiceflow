<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Plus, Trash2 } from 'lucide-vue-next'
import api from '@/api/client'

const router = useRouter()
const route = useRoute()
const isEdit = computed(() => !!route.params.id)

const clients = ref([])
const saving = ref(false)
const error = ref('')

const form = ref({
  client_id: '',
  date: new Date().toISOString().slice(0, 10),
  due_date: '',
  tax_rate: 22,
  notes: '',
  items: [{ description: '', quantity: 1, unit_price: 0 }],
})

const subtotal = computed(() =>
  form.value.items.reduce((s, i) => s + (Number(i.quantity) * Number(i.unit_price)), 0)
)
const taxAmount = computed(() => subtotal.value * (Number(form.value.tax_rate) / 100))
const total = computed(() => subtotal.value + taxAmount.value)

function fmt(val) {
  return Number(val || 0).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })
}

onMounted(async () => {
  const { data } = await api.get('/clients')
  clients.value = data

  if (isEdit.value) {
    const { data: inv } = await api.get(`/invoices/${route.params.id}`)
    form.value = {
      client_id: inv.client_id,
      date: inv.date,
      due_date: inv.due_date || '',
      tax_rate: inv.tax_rate,
      notes: inv.notes || '',
      items: inv.items.map(i => ({ description: i.description, quantity: i.quantity, unit_price: i.unit_price })),
    }
  }
})

function addItem() {
  form.value.items.push({ description: '', quantity: 1, unit_price: 0 })
}

function removeItem(i) {
  if (form.value.items.length > 1) form.value.items.splice(i, 1)
}

async function submit() {
  error.value = ''
  saving.value = true
  try {
    if (isEdit.value) {
      await api.put(`/invoices/${route.params.id}`, form.value)
    } else {
      await api.post('/invoices', form.value)
    }
    router.push('/invoices')
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : 'Errore nel salvataggio'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-8 max-w-3xl">
    <div class="mb-8">
      <button @click="router.push('/invoices')" class="text-sm text-gray-500 hover:text-gray-700 mb-2">← Indietro</button>
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Modifica fattura' : 'Nuova fattura' }}</h1>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- Header info -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Informazioni</h2>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
          <select v-model="form.client_id" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">Seleziona un cliente</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
            <input v-model="form.date" type="date" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Scadenza</label>
            <input v-model="form.due_date" type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">IVA (%)</label>
          <input v-model="form.tax_rate" type="number" min="0" max="100"
            class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>
      </div>

      <!-- Items -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Voci</h2>
        <div class="space-y-3">
          <div v-for="(item, idx) in form.items" :key="idx"
            class="grid gap-3 items-start"
            style="grid-template-columns: 1fr 80px 110px 36px">
            <div>
              <label v-if="idx === 0" class="block text-xs text-gray-500 mb-1">Descrizione</label>
              <input v-model="item.description" type="text" required placeholder="Servizio o prodotto"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label v-if="idx === 0" class="block text-xs text-gray-500 mb-1">Qtà</label>
              <input v-model="item.quantity" type="number" min="0.01" step="0.01" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label v-if="idx === 0" class="block text-xs text-gray-500 mb-1">Prezzo unit.</label>
              <input v-model="item.unit_price" type="number" min="0" step="0.01" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div :class="idx === 0 ? 'mt-5' : ''">
              <button type="button" @click="removeItem(idx)" :disabled="form.items.length === 1"
                class="p-2 hover:bg-red-50 rounded-lg text-gray-400 hover:text-red-500 transition-colors disabled:opacity-30">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <button type="button" @click="addItem"
          class="mt-4 flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
          <Plus class="w-4 h-4" /> Aggiungi voce
        </button>

        <!-- Totals -->
        <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col items-end gap-1.5">
          <div class="flex justify-between w-48 text-sm text-gray-600">
            <span>Subtotale</span><span>{{ fmt(subtotal) }}</span>
          </div>
          <div v-if="form.tax_rate > 0" class="flex justify-between w-48 text-sm text-gray-600">
            <span>IVA {{ form.tax_rate }}%</span><span>{{ fmt(taxAmount) }}</span>
          </div>
          <div class="flex justify-between w-48 text-base font-bold text-indigo-600 pt-2 border-t border-gray-200">
            <span>Totale</span><span>{{ fmt(total) }}</span>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Note</label>
        <textarea v-model="form.notes" rows="3" placeholder="Note aggiuntive (opzionale)"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
      </div>

      <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

      <div class="flex gap-3">
        <button type="button" @click="router.push('/invoices')"
          class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
          Annulla
        </button>
        <button type="submit" :disabled="saving"
          class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors">
          {{ saving ? 'Salvataggio...' : (isEdit ? 'Aggiorna' : 'Crea fattura') }}
        </button>
      </div>
    </form>
  </div>
</template>
