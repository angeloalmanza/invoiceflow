<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { TrendingUp, Clock, CheckCircle, FileText } from 'lucide-vue-next'
import api from '@/api/client'

const router = useRouter()
const stats = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/invoices/stats')
    stats.value = data
  } finally {
    loading.value = false
  }
})

function fmt(val) {
  return Number(val || 0).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })
}

const cards = [
  { key: 'total_invoiced', label: 'Totale fatturato', icon: TrendingUp, color: 'text-indigo-600', bg: 'bg-indigo-50' },
  { key: 'total_paid',     label: 'Incassato',        icon: CheckCircle, color: 'text-green-600',  bg: 'bg-green-50' },
  { key: 'total_pending',  label: 'Da incassare',     icon: Clock,       color: 'text-amber-600',  bg: 'bg-amber-50' },
]
</script>

<template>
  <div class="p-8">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
      <p class="text-gray-500 text-sm mt-1">Panoramica delle tue fatture</p>
    </div>

    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
      <div v-for="i in 3" :key="i" class="bg-white rounded-xl border border-gray-200 p-6 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-24 mb-4"></div>
        <div class="h-8 bg-gray-200 rounded w-32"></div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
      <div v-for="card in cards" :key="card.key"
        class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <span class="text-sm font-medium text-gray-500">{{ card.label }}</span>
          <div :class="[card.bg, 'p-2 rounded-lg']">
            <component :is="card.icon" :class="[card.color, 'w-5 h-5']" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ fmt(stats?.[card.key]) }}</p>
      </div>
    </div>

    <!-- Status counts -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
      <h2 class="text-base font-semibold text-gray-900 mb-4">Stato fatture</h2>
      <div class="flex gap-6">
        <div class="text-center">
          <p class="text-3xl font-bold text-gray-500">{{ stats?.count_draft ?? '–' }}</p>
          <p class="text-sm text-gray-500 mt-1">Bozze</p>
        </div>
        <div class="text-center">
          <p class="text-3xl font-bold text-blue-600">{{ stats?.count_sent ?? '–' }}</p>
          <p class="text-sm text-gray-500 mt-1">Inviate</p>
        </div>
        <div class="text-center">
          <p class="text-3xl font-bold text-green-600">{{ stats?.count_paid ?? '–' }}</p>
          <p class="text-sm text-gray-500 mt-1">Pagate</p>
        </div>
      </div>
    </div>

    <div class="flex gap-4">
      <button @click="router.push('/invoices/new')"
        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
        <FileText class="w-4 h-4" /> Nuova fattura
      </button>
      <button @click="router.push('/clients')"
        class="px-4 py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg transition-colors">
        Gestisci clienti
      </button>
    </div>
  </div>
</template>
