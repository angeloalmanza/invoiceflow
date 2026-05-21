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
  <div class="page">

    <div class="page-header">
      <div>
        <button class="back-btn" @click="router.push('/invoices')">← Fatture</button>
        <h1 class="page-title">{{ isEdit ? 'Modifica fattura' : 'Nuova fattura' }}</h1>
      </div>
    </div>

    <form @submit.prevent="submit" class="form-layout">

      <!-- Sezione sinistra: info + voci -->
      <div class="form-main">

        <!-- Info principali -->
        <div class="card">
          <h2 class="card-title">Informazioni</h2>
          <div class="fields">
            <div class="field">
              <label class="field-label">Cliente <span class="required">*</span></label>
              <select v-model="form.client_id" required class="field-input field-select">
                <option value="">Seleziona un cliente</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="field-row">
              <div class="field">
                <label class="field-label">Data <span class="required">*</span></label>
                <input v-model="form.date" type="date" required class="field-input" />
              </div>
              <div class="field">
                <label class="field-label">Scadenza</label>
                <input v-model="form.due_date" type="date" class="field-input" />
              </div>
            </div>
            <div class="field" style="max-width: 10rem;">
              <label class="field-label">IVA (%)</label>
              <input v-model="form.tax_rate" type="number" min="0" max="100" class="field-input" />
            </div>
          </div>
        </div>

        <!-- Voci fattura -->
        <div class="card">
          <h2 class="card-title">Voci</h2>

          <!-- Header colonne: visibile solo da seconda riga in su -->
          <div class="items-header">
            <span>Descrizione</span>
            <span style="text-align:right">Qtà</span>
            <span style="text-align:right">Prezzo unit.</span>
            <span></span>
          </div>

          <div class="items-list">
            <div v-for="(item, idx) in form.items" :key="idx" class="item-row">
              <input
                v-model="item.description" type="text" required
                placeholder="Es. Sviluppo sito web"
                class="field-input"
              />
              <input
                v-model="item.quantity" type="number" min="0.01" step="0.01" required
                class="field-input field-input--num"
              />
              <input
                v-model="item.unit_price" type="number" min="0" step="0.01" required
                class="field-input field-input--num"
              />
              <button
                type="button" @click="removeItem(idx)"
                :disabled="form.items.length === 1"
                class="remove-btn"
              >
                <Trash2 class="remove-icon" />
              </button>
            </div>
          </div>

          <button type="button" class="add-item-btn" @click="addItem">
            <Plus class="btn-icon" /> Aggiungi voce
          </button>
        </div>

        <!-- Note -->
        <div class="card">
          <div class="field">
            <label class="field-label">Note <span class="field-optional">(opzionale)</span></label>
            <textarea
              v-model="form.notes" rows="3"
              placeholder="Note aggiuntive per il cliente..."
              class="field-input field-textarea"
            ></textarea>
          </div>
        </div>

      </div>

      <!-- Sidebar destra: riepilogo + azioni -->
      <div class="form-sidebar">

        <!-- Totals card: sticky per rimanere visibile durante lo scroll -->
        <div class="card totals-card">
          <h2 class="card-title">Riepilogo</h2>
          <div class="totals">
            <div class="totals-row">
              <span class="totals-label">Subtotale</span>
              <span class="totals-value">{{ fmt(subtotal) }}</span>
            </div>
            <div v-if="form.tax_rate > 0" class="totals-row">
              <span class="totals-label">IVA {{ form.tax_rate }}%</span>
              <span class="totals-value">{{ fmt(taxAmount) }}</span>
            </div>
            <div class="totals-row totals-row--total">
              <span class="totals-label--total">Totale</span>
              <span class="totals-value--total">{{ fmt(total) }}</span>
            </div>
          </div>

          <p v-if="error" class="error-msg">{{ error }}</p>

          <div class="action-btns">
            <button type="submit" class="btn-primary btn-full" :disabled="saving">
              {{ saving ? 'Salvataggio...' : (isEdit ? 'Aggiorna fattura' : 'Crea fattura') }}
            </button>
            <button type="button" class="btn-secondary btn-full" @click="router.push('/invoices')">
              Annulla
            </button>
          </div>
        </div>

      </div>
    </form>

  </div>
</template>

<style scoped>
.page { padding: 2.5rem; }

.page-header { margin-bottom: 1.75rem; }
.back-btn {
  display: block; margin-bottom: 0.5rem;
  font-size: 0.875rem; color: #6b7280;
  background: transparent; border: none;
  cursor: pointer; padding: 0; transition: color 0.15s;
}
.back-btn:hover { color: #111827; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #111827; letter-spacing: -0.025em; }

/* Layout a due colonne: form principale + sidebar riepilogo */
.form-layout { display: grid; grid-template-columns: 1fr 18rem; gap: 1.25rem; align-items: start; }
@media (max-width: 768px) { .form-layout { grid-template-columns: 1fr; } }

.form-main { display: flex; flex-direction: column; gap: 1.25rem; }

.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.875rem; padding: 1.5rem; }
.card-title { font-size: 0.9375rem; font-weight: 600; color: #111827; margin-bottom: 1.25rem; }

/* Fields */
.fields { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.field-label { font-size: 0.8125rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem; }
.field-optional { font-weight: 400; color: #9ca3af; }
.required { color: #4f46e5; }

/* Inputs — design system coerente con l'intera app */
.field-input {
  padding: 0.625rem 0.875rem;
  border: 1.5px solid #d1d5db;
  border-radius: 0.625rem;
  font-size: 0.875rem;
  color: #111827;
  background: #fff;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  width: 100%;
  box-sizing: border-box;
}
.field-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
}
.field-input::placeholder { color: #9ca3af; }
.field-select { cursor: pointer; }
.field-input--num { text-align: right; }
.field-textarea { resize: vertical; min-height: 5rem; font-family: inherit; }

/* Items table */
.items-header {
  display: grid;
  grid-template-columns: 1fr 5rem 7rem 2rem;
  gap: 0.75rem;
  padding-bottom: 0.5rem;
  margin-bottom: 0.5rem;
  border-bottom: 1px solid #f3f4f6;
  font-size: 0.75rem;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.items-list { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem; }
.item-row {
  display: grid;
  grid-template-columns: 1fr 5rem 7rem 2rem;
  gap: 0.75rem;
  align-items: center;
}

.remove-btn {
  display: flex; align-items: center; justify-content: center;
  width: 2rem; height: 2rem;
  border: none; background: transparent;
  color: #d1d5db; border-radius: 0.5rem;
  cursor: pointer; transition: background 0.15s, color 0.15s;
}
.remove-btn:hover:not(:disabled) { background: #fef2f2; color: #ef4444; }
.remove-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.remove-icon { width: 0.9375rem; height: 0.9375rem; }

.add-item-btn {
  display: inline-flex; align-items: center; gap: 0.375rem;
  font-size: 0.875rem; font-weight: 500; color: #4f46e5;
  background: transparent; border: none;
  cursor: pointer; padding: 0;
  transition: color 0.15s;
}
.add-item-btn:hover { color: #4338ca; }

/* Sidebar totals — sticky top per rimanere visibile durante lo scroll del form */
.totals-card { position: sticky; top: 1.5rem; }

.totals { display: flex; flex-direction: column; gap: 0.625rem; margin-bottom: 1.25rem; }
.totals-row { display: flex; justify-content: space-between; }
.totals-label { font-size: 0.875rem; color: #6b7280; }
.totals-value { font-size: 0.875rem; color: #374151; font-weight: 500; }
.totals-row--total {
  padding-top: 0.75rem;
  margin-top: 0.25rem;
  border-top: 1.5px solid #e5e7eb;
}
.totals-label--total { font-size: 1rem; font-weight: 700; color: #111827; }
.totals-value--total { font-size: 1rem; font-weight: 700; color: #4f46e5; }

.error-msg {
  font-size: 0.8125rem; color: #dc2626;
  background: #fef2f2; border: 1px solid #fecaca;
  border-radius: 0.625rem; padding: 0.625rem 0.875rem;
  margin-bottom: 1rem;
}

.action-btns { display: flex; flex-direction: column; gap: 0.625rem; }
.btn-full { width: 100%; justify-content: center; }

/* Buttons */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.6875rem 1.125rem;
  background: #4f46e5; color: #fff;
  font-size: 0.875rem; font-weight: 600;
  border: none; border-radius: 0.625rem;
  cursor: pointer; transition: background 0.15s;
}
.btn-primary:hover { background: #4338ca; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-secondary {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 0.6875rem 1.125rem;
  background: #fff; color: #374151;
  font-size: 0.875rem; font-weight: 500;
  border: 1.5px solid #e5e7eb; border-radius: 0.625rem;
  cursor: pointer; transition: background 0.15s;
}
.btn-secondary:hover { background: #f9fafb; }
.btn-icon { width: 1rem; height: 1rem; }
</style>
