<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { TrendingUp, Clock, CheckCircle, FileText, Users } from 'lucide-vue-next'
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
  { key: 'total_invoiced', label: 'Totale fatturato', icon: TrendingUp, accent: '#4f46e5', bg: 'rgba(79,70,229,0.08)' },
  { key: 'total_paid',     label: 'Incassato',        icon: CheckCircle, accent: '#16a34a', bg: 'rgba(22,163,74,0.08)' },
  { key: 'total_pending',  label: 'Da incassare',     icon: Clock,       accent: '#d97706', bg: 'rgba(217,119,6,0.08)' },
]

const statusItems = [
  { key: 'count_draft', label: 'Bozze',   color: '#6b7280' },
  { key: 'count_sent',  label: 'Inviate', color: '#2563eb' },
  { key: 'count_paid',  label: 'Pagate',  color: '#16a34a' },
]
</script>

<template>
  <div class="page">

    <!-- Page header: titolo + saluto + CTA principale in linea -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Panoramica delle tue fatture</p>
      </div>
      <button class="btn-primary" @click="router.push('/invoices/new')">
        <FileText class="btn-icon" />
        Nuova fattura
      </button>
    </div>

    <!-- Stat cards: skeleton durante loading, poi dati reali -->
    <div class="stats-grid">
      <template v-if="loading">
        <div v-for="i in 3" :key="i" class="stat-card stat-card--skeleton">
          <div class="skeleton-label"></div>
          <div class="skeleton-value"></div>
        </div>
      </template>
      <template v-else>
        <!--
          Icona colorata in alto a destra: affordance visiva immediata del tipo di metrica.
          Numero in text-3xl: gerarchia chiara — il dato è il protagonista.
        -->
        <div v-for="card in cards" :key="card.key" class="stat-card">
          <div class="stat-header">
            <span class="stat-label">{{ card.label }}</span>
            <div class="stat-icon-wrap" :style="{ background: card.bg }">
              <component :is="card.icon" class="stat-icon" :style="{ color: card.accent }" />
            </div>
          </div>
          <p class="stat-value">{{ fmt(stats?.[card.key]) }}</p>
        </div>
      </template>
    </div>

    <!-- Status card + CTA secondaria affiancati -->
    <div class="bottom-row">
      <!--
        Stato fatture: 3 colonne con divisori — quick overview senza navigare.
        Numeri grandi con colore semantico (grigio bozze, blu inviate, verde pagate).
      -->
      <div class="card status-card">
        <h2 class="card-title">Stato fatture</h2>
        <div class="status-row">
          <div v-for="s in statusItems" :key="s.key" class="status-item">
            <span class="status-count" :style="{ color: s.color }">
              {{ loading ? '–' : (stats?.[s.key] ?? '–') }}
            </span>
            <span class="status-label">{{ s.label }}</span>
          </div>
        </div>
      </div>

      <!-- Quick actions: accesso rapido alle sezioni principali -->
      <div class="card actions-card">
        <h2 class="card-title">Azioni rapide</h2>
        <div class="actions-list">
          <button class="btn-primary btn-full" @click="router.push('/invoices/new')">
            <FileText class="btn-icon" /> Nuova fattura
          </button>
          <button class="btn-secondary btn-full" @click="router.push('/clients')">
            <Users class="btn-icon" /> Gestisci clienti
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.page { padding: 2.5rem; max-width: 72rem; }

/* Header: title + CTA affiancati, flex justify-between */
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 2rem;
}
.page-title { font-size: 1.5rem; font-weight: 700; color: #111827; letter-spacing: -0.025em; }
.page-subtitle { font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; }

/* Stat grid: 3 colonne su md+ */
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.25rem; }
@media (max-width: 640px) { .stats-grid { grid-template-columns: 1fr; } }

/* Stat card */
.stat-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 0.875rem;
  padding: 1.5rem;
}
.stat-card--skeleton { animation: pulse 1.5s ease-in-out infinite; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.5; } }
.skeleton-label { height: 0.875rem; background: #e5e7eb; border-radius: 0.25rem; width: 7rem; margin-bottom: 1rem; }
.skeleton-value { height: 1.75rem; background: #e5e7eb; border-radius: 0.25rem; width: 9rem; }

.stat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.stat-label { font-size: 0.8125rem; font-weight: 500; color: #6b7280; }
.stat-icon-wrap { padding: 0.5rem; border-radius: 0.5rem; display: flex; }
.stat-icon { width: 1.125rem; height: 1.125rem; }
/* text-3xl per il numero: gerarchia forte, il dato è il protagonista */
.stat-value { font-size: 1.75rem; font-weight: 700; color: #111827; letter-spacing: -0.03em; }

/* Bottom row */
.bottom-row { display: grid; grid-template-columns: 1fr 18rem; gap: 1.25rem; }
@media (max-width: 768px) { .bottom-row { grid-template-columns: 1fr; } }

.card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 0.875rem;
  padding: 1.5rem;
}
.card-title { font-size: 0.9375rem; font-weight: 600; color: #111827; margin-bottom: 1.25rem; }

/* Status row: 3 colonne con divisori verticali */
.status-row { display: flex; gap: 0; }
.status-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem 0;
}
.status-item:not(:last-child) { border-right: 1px solid #f3f4f6; }
.status-count { font-size: 2rem; font-weight: 700; letter-spacing: -0.03em; line-height: 1; }
.status-label { font-size: 0.8125rem; color: #6b7280; margin-top: 0.375rem; }

/* Actions */
.actions-card { display: flex; flex-direction: column; }
.actions-list { display: flex; flex-direction: column; gap: 0.75rem; }
.btn-full { width: 100%; justify-content: center; }

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

.btn-secondary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.625rem 1.125rem;
  background: #fff; color: #374151;
  font-size: 0.875rem; font-weight: 500;
  border: 1.5px solid #e5e7eb; border-radius: 0.625rem;
  cursor: pointer; transition: background 0.15s, border-color 0.15s;
}
.btn-secondary:hover { background: #f9fafb; border-color: #d1d5db; }
.btn-icon { width: 1rem; height: 1rem; }
</style>
