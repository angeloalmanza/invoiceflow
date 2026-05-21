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
  draft: { label: 'Bozza',   color: '#6b7280', bg: '#f3f4f6' },
  sent:  { label: 'Inviata', color: '#2563eb', bg: '#eff6ff' },
  paid:  { label: 'Pagata',  color: '#16a34a', bg: '#f0fdf4' },
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
  <div class="page">

    <div class="page-header">
      <div>
        <h1 class="page-title">Fatture</h1>
        <p class="page-subtitle">{{ invoices.length }} fatture totali</p>
      </div>
      <button class="btn-primary" @click="router.push('/invoices/new')">
        <Plus class="btn-icon" /> Nuova fattura
      </button>
    </div>

    <!-- Filter tabs: pill style, active con background brand -->
    <div class="filter-bar">
      <button
        v-for="f in filters" :key="f.value"
        class="filter-btn"
        :class="filter === f.value ? 'filter-btn--active' : ''"
        @click="filter = f.value"
      >
        {{ f.label }}
      </button>
    </div>

    <!-- Skeleton loading -->
    <div v-if="loading" class="skeleton-list">
      <div v-for="i in 5" :key="i" class="skeleton-row"></div>
    </div>

    <!-- Empty state: illustrazione + testo guida l'utente all'azione -->
    <div v-else-if="!filtered.length" class="empty-state">
      <div class="empty-icon-wrap">
        <FileText class="empty-icon" />
      </div>
      <p class="empty-title">Nessuna fattura trovata</p>
      <p class="empty-sub">Crea la tua prima fattura per iniziare</p>
      <button class="btn-primary" style="margin-top:1.25rem" @click="router.push('/invoices/new')">
        <Plus class="btn-icon" /> Nuova fattura
      </button>
    </div>

    <!-- Table -->
    <div v-else class="table-card">
      <table class="table">
        <thead>
          <tr>
            <th class="th">Numero</th>
            <th class="th">Cliente</th>
            <th class="th">Data</th>
            <th class="th">Stato</th>
            <th class="th th--right">Totale</th>
            <th class="th"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="inv in filtered" :key="inv.id"
            class="tr"
            @click="router.push(`/invoices/${inv.id}`)"
          >
            <!-- Numero fattura in indigo: affordance cliccabile immediata -->
            <td class="td td--number">{{ inv.number }}</td>
            <td class="td td--name">{{ inv.client?.name }}</td>
            <td class="td td--muted">{{ fmtDate(inv.date) }}</td>
            <td class="td">
              <span
                class="badge"
                :style="{ color: statusMap[inv.status]?.color, background: statusMap[inv.status]?.bg }"
              >{{ statusMap[inv.status]?.label }}</span>
            </td>
            <td class="td td--amount">{{ fmt(inv.total) }}</td>
            <td class="td td--actions" @click.stop>
              <button class="action-btn" title="Visualizza" @click="router.push(`/invoices/${inv.id}`)">
                <Eye class="action-icon" />
              </button>
              <button class="action-btn" title="Scarica PDF" @click="downloadPdf(inv)">
                <Download class="action-icon" />
              </button>
              <button class="action-btn action-btn--danger" title="Elimina" @click="remove(inv.id)">
                <Trash2 class="action-icon" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</template>

<style scoped>
.page { padding: 2.5rem; }

.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #111827; letter-spacing: -0.025em; }
.page-subtitle { font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; }

/* Filter bar */
.filter-bar { display: flex; gap: 0.375rem; margin-bottom: 1.5rem; }
.filter-btn {
  padding: 0.4375rem 0.875rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 500;
  border: 1.5px solid #e5e7eb;
  background: #fff;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.15s;
}
.filter-btn:hover { border-color: #c7d2fe; color: #4f46e5; }
/* Active: background brand, no border */
.filter-btn--active { background: #4f46e5; color: #fff; border-color: #4f46e5; }

/* Skeleton */
.skeleton-list { display: flex; flex-direction: column; gap: 0.75rem; }
.skeleton-row {
  height: 4rem; background: #fff;
  border: 1px solid #e5e7eb; border-radius: 0.75rem;
  animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }

/* Empty state: centrato con icona, guida l'utente all'azione */
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

/* Table card */
.table-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.875rem; overflow: hidden; }
.table { width: 100%; border-collapse: collapse; }

.th {
  padding: 0.875rem 1.25rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}
.th--right { text-align: right; }

/* Row: min-height 56px, hover + cursor pointer per affordance cliccabile */
.tr { cursor: pointer; transition: background 0.1s; }
.tr:not(:last-child) { border-bottom: 1px solid #f3f4f6; }
.tr:hover { background: #f9fafb; }

.td { padding: 1rem 1.25rem; font-size: 0.875rem; color: #374151; vertical-align: middle; }
.td--number { font-weight: 600; color: #4f46e5; }
.td--name { font-weight: 500; color: #111827; }
.td--muted { color: #6b7280; }
.td--amount { text-align: right; font-weight: 600; color: #111827; }
.td--actions { text-align: right; }

/* Badge: pill con colore semantico */
.badge {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

/* Action buttons: invisible finché hover della riga, poi visibili */
.action-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 2rem; height: 2rem;
  border-radius: 0.375rem;
  border: none; background: transparent;
  color: #9ca3af; cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.action-btn:hover { background: #f3f4f6; color: #374151; }
.action-btn--danger:hover { background: #fef2f2; color: #ef4444; }
.action-icon { width: 0.9375rem; height: 0.9375rem; }

/* Buttons */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.625rem 1.125rem;
  background: #4f46e5; color: #fff;
  font-size: 0.875rem; font-weight: 600;
  border: none; border-radius: 0.625rem;
  cursor: pointer; transition: background 0.15s;
}
.btn-primary:hover { background: #4338ca; }
.btn-icon { width: 1rem; height: 1rem; }
</style>
