<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Download, Pencil, ArrowLeft } from 'lucide-vue-next'
import api from '@/api/client'

const router = useRouter()
const route = useRoute()
const invoice = ref(null)
const loading = ref(true)
const updating = ref(false)

onMounted(async () => {
  const { data } = await api.get(`/invoices/${route.params.id}`)
  invoice.value = data
  loading.value = false
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

const nextStatus = { draft: 'sent', sent: 'paid' }

async function advanceStatus() {
  const next = nextStatus[invoice.value.status]
  if (!next) return
  updating.value = true
  const { data } = await api.put(`/invoices/${invoice.value.id}`, { status: next })
  invoice.value = data
  updating.value = false
}

async function downloadPdf() {
  const resp = await api.get(`/invoices/${invoice.value.id}/pdf`, { responseType: 'blob' })
  const url = URL.createObjectURL(resp.data)
  const a = document.createElement('a')
  a.href = url
  a.download = `fattura-${invoice.value.number}.pdf`
  a.click()
  URL.revokeObjectURL(url)
}

const statusActionLabel = { draft: 'Segna come Inviata', sent: 'Segna come Pagata' }
</script>

<template>
  <div class="p-8 max-w-3xl">
    <button @click="router.push('/invoices')" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
      <ArrowLeft class="w-4 h-4" /> Indietro
    </button>

    <div v-if="loading" class="bg-white rounded-xl border border-gray-200 p-8 animate-pulse">
      <div class="h-6 bg-gray-200 rounded w-40 mb-4"></div>
      <div class="h-4 bg-gray-200 rounded w-64"></div>
    </div>

    <template v-else-if="invoice">
      <!-- Header -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-bold text-indigo-600">{{ invoice.number }}</h1>
            <p class="text-gray-500 text-sm mt-1">{{ invoice.client?.name }}</p>
          </div>
          <div class="flex items-center gap-2 flex-wrap justify-end">
            <span class="px-3 py-1 rounded-full text-xs font-semibold" :class="statusMap[invoice.status]?.cls">
              {{ statusMap[invoice.status]?.label }}
            </span>
            <button v-if="nextStatus[invoice.status]" @click="advanceStatus" :disabled="updating"
              class="px-3 py-1.5 border border-indigo-300 text-indigo-600 text-xs font-medium rounded-lg hover:bg-indigo-50 transition-colors disabled:opacity-50">
              {{ statusActionLabel[invoice.status] }}
            </button>
            <button @click="downloadPdf"
              class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors">
              <Download class="w-3.5 h-3.5" /> PDF
            </button>
            <button @click="router.push(`/invoices/${invoice.id}/edit`)"
              class="flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 transition-colors">
              <Pencil class="w-3.5 h-3.5" /> Modifica
            </button>
          </div>
        </div>

        <div class="flex gap-8 mt-6 pt-6 border-t border-gray-100">
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Data</p>
            <p class="text-sm font-semibold mt-1">{{ fmtDate(invoice.date) }}</p>
          </div>
          <div v-if="invoice.due_date">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Scadenza</p>
            <p class="text-sm font-semibold mt-1">{{ fmtDate(invoice.due_date) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">IVA</p>
            <p class="text-sm font-semibold mt-1">{{ invoice.tax_rate }}%</p>
          </div>
        </div>
      </div>

      <!-- Parties -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4 grid grid-cols-2 gap-6">
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Cliente</p>
          <p class="font-semibold">{{ invoice.client?.name }}</p>
          <p v-if="invoice.client?.email" class="text-sm text-gray-500">{{ invoice.client.email }}</p>
          <p v-if="invoice.client?.address" class="text-sm text-gray-500">{{ invoice.client.address }}</p>
          <p v-if="invoice.client?.city" class="text-sm text-gray-500">{{ invoice.client.city }}</p>
          <p v-if="invoice.client?.vat_number" class="text-sm text-gray-500">P.IVA: {{ invoice.client.vat_number }}</p>
        </div>
      </div>

      <!-- Items table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left text-xs font-medium text-gray-500 uppercase px-5 py-3">Descrizione</th>
              <th class="text-right text-xs font-medium text-gray-500 uppercase px-5 py-3">Qtà</th>
              <th class="text-right text-xs font-medium text-gray-500 uppercase px-5 py-3">Prezzo unit.</th>
              <th class="text-right text-xs font-medium text-gray-500 uppercase px-5 py-3">Totale</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in invoice.items" :key="item.id">
              <td class="px-5 py-4 text-sm">{{ item.description }}</td>
              <td class="px-5 py-4 text-sm text-right">{{ item.quantity }}</td>
              <td class="px-5 py-4 text-sm text-right">{{ fmt(item.unit_price) }}</td>
              <td class="px-5 py-4 text-sm font-medium text-right">{{ fmt(item.total) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="px-5 py-4 border-t border-gray-100 flex flex-col items-end gap-1.5">
          <div class="flex justify-between w-52 text-sm text-gray-600">
            <span>Subtotale</span><span>{{ fmt(invoice.subtotal) }}</span>
          </div>
          <div v-if="invoice.tax_rate > 0" class="flex justify-between w-52 text-sm text-gray-600">
            <span>IVA {{ invoice.tax_rate }}%</span><span>{{ fmt(invoice.tax_amount) }}</span>
          </div>
          <div class="flex justify-between w-52 text-base font-bold text-indigo-600 pt-2 border-t border-gray-200">
            <span>Totale</span><span>{{ fmt(invoice.total) }}</span>
          </div>
        </div>
      </div>

      <div v-if="invoice.notes" class="bg-white rounded-xl border border-gray-200 p-6">
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Note</p>
        <p class="text-sm text-gray-700">{{ invoice.notes }}</p>
      </div>
    </template>
  </div>
</template>
