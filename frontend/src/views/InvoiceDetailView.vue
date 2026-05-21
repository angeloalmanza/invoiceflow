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
  draft: { label: 'Bozza',   color: '#6b7280', bg: '#f3f4f6' },
  sent:  { label: 'Inviata', color: '#2563eb', bg: '#eff6ff' },
  paid:  { label: 'Pagata',  color: '#16a34a', bg: '#f0fdf4' },
}

const nextStatus = { draft: 'sent', sent: 'paid' }
const statusActionLabel = { draft: 'Segna come Inviata', sent: 'Segna come Pagata' }

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
</script>

<template>
  <div class="page">

    <!-- Back link: micro-navigazione, affordance chiara -->
    <button class="back-btn" @click="router.push('/invoices')">
      <ArrowLeft class="back-icon" /> Fatture
    </button>

    <!-- Skeleton -->
    <div v-if="loading" class="card skeleton-card">
      <div class="sk-h sk-h--lg" style="width:12rem;margin-bottom:0.75rem"></div>
      <div class="sk-h" style="width:18rem"></div>
    </div>

    <template v-else-if="invoice">

      <!-- Header card: numero + stato + azioni principali -->
      <div class="card header-card">
        <div class="header-top">
          <div>
            <!-- Numero fattura: titolo della pagina -->
            <h1 class="invoice-number">{{ invoice.number }}</h1>
            <p class="invoice-client">{{ invoice.client?.name }}</p>
          </div>
          <!-- Azioni: ordine per frequenza d'uso (PDF > modifica > avanza stato) -->
          <div class="header-actions">
            <span
              class="status-badge"
              :style="{ color: statusMap[invoice.status]?.color, background: statusMap[invoice.status]?.bg }"
            >{{ statusMap[invoice.status]?.label }}</span>
            <button v-if="nextStatus[invoice.status]" class="btn-ghost" :disabled="updating" @click="advanceStatus">
              {{ statusActionLabel[invoice.status] }}
            </button>
            <button class="btn-secondary" @click="router.push(`/invoices/${invoice.id}/edit`)">
              <Pencil class="btn-icon" /> Modifica
            </button>
            <button class="btn-primary" @click="downloadPdf">
              <Download class="btn-icon" /> PDF
            </button>
          </div>
        </div>

        <!-- Meta info: griglia con Data / Scadenza / IVA -->
        <div class="invoice-meta">
          <div class="meta-item">
            <span class="meta-label">Data emissione</span>
            <span class="meta-value">{{ fmtDate(invoice.date) }}</span>
          </div>
          <div v-if="invoice.due_date" class="meta-item">
            <span class="meta-label">Scadenza</span>
            <span class="meta-value">{{ fmtDate(invoice.due_date) }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Aliquota IVA</span>
            <span class="meta-value">{{ invoice.tax_rate }}%</span>
          </div>
        </div>
      </div>

      <!-- Client info card -->
      <div class="card client-card">
        <p class="section-label">Destinatario</p>
        <p class="client-name">{{ invoice.client?.name }}</p>
        <p v-if="invoice.client?.email" class="client-detail">{{ invoice.client.email }}</p>
        <p v-if="invoice.client?.address" class="client-detail">{{ invoice.client.address }}</p>
        <p v-if="invoice.client?.city" class="client-detail">{{ invoice.client.city }}</p>
        <p v-if="invoice.client?.vat_number" class="client-detail">P.IVA {{ invoice.client.vat_number }}</p>
      </div>

      <!-- Items table -->
      <div class="card table-card">
        <table class="table">
          <thead>
            <tr>
              <th class="th">Descrizione</th>
              <th class="th th--right">Qtà</th>
              <th class="th th--right">Prezzo unit.</th>
              <th class="th th--right">Totale</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in invoice.items" :key="item.id" class="tr">
              <td class="td">{{ item.description }}</td>
              <td class="td td--right td--muted">{{ item.quantity }}</td>
              <td class="td td--right td--muted">{{ fmt(item.unit_price) }}</td>
              <td class="td td--right td--bold">{{ fmt(item.total) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Totals: allineati a destra, separatore visivo prima del totale finale -->
        <div class="totals">
          <div class="totals-row">
            <span class="totals-label">Subtotale</span>
            <span class="totals-value">{{ fmt(invoice.subtotal) }}</span>
          </div>
          <div v-if="invoice.tax_rate > 0" class="totals-row">
            <span class="totals-label">IVA {{ invoice.tax_rate }}%</span>
            <span class="totals-value">{{ fmt(invoice.tax_amount) }}</span>
          </div>
          <div class="totals-row totals-row--total">
            <span class="totals-label--total">Totale</span>
            <span class="totals-value--total">{{ fmt(invoice.total) }}</span>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="invoice.notes" class="card notes-card">
        <p class="section-label">Note</p>
        <p class="notes-text">{{ invoice.notes }}</p>
      </div>

    </template>
  </div>
</template>

<style scoped>
/* Contenuto centrato con max-width — migliore leggibilità per documenti */
.page { padding: 2.5rem; max-width: 52rem; }

/* Back button: link testuale con icona, non un bottone prominente */
.back-btn {
  display: inline-flex; align-items: center; gap: 0.375rem;
  font-size: 0.875rem; font-weight: 500; color: #6b7280;
  background: transparent; border: none; cursor: pointer;
  padding: 0; margin-bottom: 1.5rem;
  transition: color 0.15s;
}
.back-btn:hover { color: #111827; }
.back-icon { width: 1rem; height: 1rem; }

/* Skeleton */
.skeleton-card { animation: pulse 1.5s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
.sk-h { height: 1rem; background: #e5e7eb; border-radius: 0.25rem; }
.sk-h--lg { height: 1.75rem; }

.card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 0.875rem;
  padding: 1.5rem;
  margin-bottom: 1rem;
}

/* Header card */
.header-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.invoice-number { font-size: 1.5rem; font-weight: 700; color: #4f46e5; letter-spacing: -0.025em; }
.invoice-client { font-size: 0.9375rem; color: #6b7280; margin-top: 0.25rem; }

.header-actions { display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap; }

.status-badge {
  display: inline-block;
  padding: 0.3125rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

/* Meta info: divisore sopra, grid orizzontale */
.invoice-meta {
  display: flex;
  gap: 2.5rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #f3f4f6;
}
.meta-item { display: flex; flex-direction: column; gap: 0.25rem; }
.meta-label { font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em; }
.meta-value { font-size: 0.9375rem; font-weight: 600; color: #111827; }

/* Client card */
.client-card { padding: 1.25rem 1.5rem; }
.section-label { font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.625rem; }
.client-name { font-size: 0.9375rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem; }
.client-detail { font-size: 0.875rem; color: #6b7280; line-height: 1.6; }

/* Table */
.table-card { padding: 0; overflow: hidden; }
.table { width: 100%; border-collapse: collapse; }
.th {
  padding: 0.875rem 1.25rem;
  text-align: left;
  font-size: 0.75rem; font-weight: 600; color: #6b7280;
  text-transform: uppercase; letter-spacing: 0.04em;
  background: #f9fafb; border-bottom: 1px solid #e5e7eb;
}
.th--right { text-align: right; }
.tr:not(:last-child) { border-bottom: 1px solid #f9fafb; }
.td { padding: 1rem 1.25rem; font-size: 0.875rem; color: #374151; }
.td--right { text-align: right; }
.td--muted { color: #6b7280; }
.td--bold { font-weight: 600; color: #111827; }

/* Totals */
.totals { padding: 1.25rem 1.5rem; border-top: 1px solid #f3f4f6; display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem; }
.totals-row { display: flex; justify-content: space-between; width: 14rem; }
.totals-label { font-size: 0.875rem; color: #6b7280; }
.totals-value { font-size: 0.875rem; color: #374151; font-weight: 500; }
.totals-row--total {
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1.5px solid #e5e7eb;
}
.totals-label--total { font-size: 1rem; font-weight: 700; color: #111827; }
.totals-value--total { font-size: 1rem; font-weight: 700; color: #4f46e5; }

/* Notes */
.notes-card { padding: 1.25rem 1.5rem; }
.notes-text { font-size: 0.875rem; color: #6b7280; line-height: 1.7; }

/* Buttons */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #4f46e5; color: #fff;
  font-size: 0.8125rem; font-weight: 600;
  border: none; border-radius: 0.5rem;
  cursor: pointer; transition: background 0.15s;
}
.btn-primary:hover { background: #4338ca; }

.btn-secondary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #fff; color: #374151;
  font-size: 0.8125rem; font-weight: 500;
  border: 1.5px solid #e5e7eb; border-radius: 0.5rem;
  cursor: pointer; transition: background 0.15s;
}
.btn-secondary:hover { background: #f9fafb; }

.btn-ghost {
  display: inline-flex; align-items: center;
  padding: 0.5rem 1rem;
  background: transparent; color: #4f46e5;
  font-size: 0.8125rem; font-weight: 500;
  border: 1.5px solid #c7d2fe; border-radius: 0.5rem;
  cursor: pointer; transition: background 0.15s;
}
.btn-ghost:hover { background: #eef2ff; }
.btn-ghost:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-icon { width: 0.875rem; height: 0.875rem; }
</style>
