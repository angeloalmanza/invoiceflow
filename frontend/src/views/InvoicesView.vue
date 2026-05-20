<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, FileText, Download, Eye, Trash2 } from 'lucide-vue-next'
import api from '@/api/client'

const router = useRouter()
const invoices = ref([])
const loading = ref(true)
const filter = ref('all')

onMounted(async () => {
  const { data } = await api.get('/invoices')
  invoices.value = data
  loading.value = false
})

const filtered = computed(() => {
  if (filter.value === 'all') return invoices.value
  return invoices.value.filter(i => i.status === filter.value)
})

function fmt(val) {
  return Number(val || 0).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })
}

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('it-IT') : '—'
}

const statusMap = {
  draft: { label: 'Bozza',   cls: 'bg-gray-100 text-gray-600' },
  sent:  { label: 'Inviata', cls: 'bg-blue-100 text-blue-600' },
  paid:  { label: 'Pagata',  cls: 'bg-green-100 text-green-600' },
}

async function downloadPdf(inv) {
  const resp = await api.get(`/invoices/${inv.id}/pdf`, { responseType: 'blob' })
  const url = URL.createObjectURL(resp.data)
  const a = document.createElement('a')
  a.href = url
  a.download = `fattura-${inv.number}.pdf`
  a.click()
  URL.revokeObjectURL(url)
}

async function remove(id) {
  if (!confirm('Eliminare questa fattura?')) return
  await api.delete(`/invoices/${id}`)
  invoices.value = invoices.value.filter(i => i.id !== id)
}

const filters = [
  { value: 'all',   label: 'Tutte' },
  { value: 'draft', label: 'Bozze' },
  { value: 'sent',  label: 'Inviate' },
  { value: 'paid',  label: 'Pagate' },
]
</script>

<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Fatture</h1>
        <p class="text-gray-500 text-sm mt-1">{{ invoices.length }} fatture totali</p>
      </div>
      <button @click="router.push('/invoices/new')"
        class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
        <Plus class="w-4 h-4" /> Nuova fattura
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-2 mb-6">
      <button v-for="f in filters" :key="f.value" @click="filter = f.value"
        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
        :class="filter === f.value ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'">
        {{ f.label }}
      </button>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="bg-white rounded-xl border border-gray-200 p-5 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-32 mb-2"></div>
        <div class="h-3 bg-gray-200 rounded w-48"></div>
      </div>
    </div>

    <div v-else-if="!filtered.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
      <FileText class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500">Nessuna fattura trovata.</p>
    </div>

    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Numero</th>
            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Cliente</th>
            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Data</th>
            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Stato</th>
            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Totale</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="inv in filtered" :key="inv.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-4 text-sm font-medium text-indigo-600">{{ inv.number }}</td>
            <td class="px-5 py-4 text-sm text-gray-900">{{ inv.client?.name }}</td>
            <td class="px-5 py-4 text-sm text-gray-500">{{ fmtDate(inv.date) }}</td>
            <td class="px-5 py-4">
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                :class="statusMap[inv.status]?.cls">{{ statusMap[inv.status]?.label }}</span>
            </td>
            <td class="px-5 py-4 text-sm font-semibold text-gray-900 text-right">{{ fmt(inv.total) }}</td>
            <td class="px-5 py-4">
              <div class="flex items-center gap-1 justify-end">
                <button @click="router.push(`/invoices/${inv.id}`)" title="Visualizza"
                  class="p-1.5 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-gray-600 transition-colors">
                  <Eye class="w-4 h-4" />
                </button>
                <button @click="downloadPdf(inv)" title="Scarica PDF"
                  class="p-1.5 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-gray-600 transition-colors">
                  <Download class="w-4 h-4" />
                </button>
                <button @click="remove(inv.id)" title="Elimina"
                  class="p-1.5 hover:bg-red-50 rounded-lg text-gray-400 hover:text-red-500 transition-colors">
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
