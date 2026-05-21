<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Mail, Lock, ArrowRight } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Credenziali non valide'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">

    <!-- Pannello brand — split-screen separa contesto da azione, riduce carico cognitivo -->
    <div class="brand-panel">
      <div class="brand-orb brand-orb--tl"></div>
      <div class="brand-orb brand-orb--mr"></div>
      <div class="brand-orb brand-orb--bl"></div>

      <span class="brand-logo">InvoiceFlow</span>

      <!-- flex-1 centra il titolo verticalmente — tecnica usata da Stripe, Linear -->
      <div class="brand-content">
        <h2 class="brand-heading">
          Gestisci le tue<br>fatture in modo<br>professionale.
        </h2>
        <p class="brand-sub">
          Crea, invia e monitora le tue fatture<br>da un'unica piattaforma.
        </p>
      </div>

      <span class="brand-copy">© 2026 InvoiceFlow</span>
    </div>

    <!-- Pannello form — bianco puro per massimo contrasto col pannello brand -->
    <div class="form-panel">
      <div class="form-container">

        <!-- Logo mobile-only: brand recall su schermi piccoli -->
        <div class="mobile-logo">InvoiceFlow</div>

        <!-- Header: text-3xl più autorevole, mb-10 respiro pre-form -->
        <div class="form-header">
          <h1 class="form-title">Bentornato</h1>
          <p class="form-subtitle">Accedi al tuo account per continuare</p>
        </div>

        <form @submit.prevent="submit" class="form-body">

          <!-- Field group: label + input-wrap come unità semantica -->
          <div class="field">
            <label class="field-label">Email</label>
            <!-- input-wrap gestisce border + focus ring via CSS scoped -->
            <div class="input-wrap">
              <Mail class="input-icon" />
              <input
                v-model="email" type="email" required
                autocomplete="email" placeholder="nome@esempio.com"
                class="input-field"
              />
            </div>
          </div>

          <div class="field">
            <label class="field-label">Password</label>
            <div class="input-wrap">
              <Lock class="input-icon" />
              <input
                v-model="password" type="password" required
                autocomplete="current-password" placeholder="••••••••"
                class="input-field"
              />
            </div>
          </div>

          <div v-if="error" class="error-box">{{ error }}</div>

          <!--
            CTA: py allineato all'altezza degli input (56px) → coerenza verticale.
            mt-8 Gestalt proximity: gap visivo separa "dati" da "azione".
          -->
          <button type="submit" :disabled="loading" class="submit-btn">
            <span>{{ loading ? 'Accesso in corso...' : 'Accedi' }}</span>
            <ArrowRight v-if="!loading" class="btn-icon" />
          </button>
        </form>

        <!-- Link secondario: distanza respirata evita confusione con la CTA primaria -->
        <p class="form-footer">
          Non hai un account?
          <RouterLink to="/register" class="form-link">Registrati</RouterLink>
        </p>

      </div>
    </div>

  </div>
</template>

<style scoped>
/* Layout */
.auth-page { display: flex; min-height: 100vh; }

/* Brand panel */
.brand-panel {
  display: none;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  width: 50%;
  padding: 4rem 3.5rem 4rem 5rem;
  background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%);
}
@media (min-width: 1024px) { .brand-panel { display: flex; } }

.brand-orb {
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,0.05);
}
.brand-orb--tl { width: 20rem; height: 20rem; top: -5rem; left: -5rem; }
.brand-orb--mr { width: 24rem; height: 24rem; top: 50%; right: -7rem; }
.brand-orb--bl { width: 18rem; height: 18rem; bottom: -4rem; left: 30%; }

.brand-logo {
  position: relative; z-index: 10;
  font-size: 1.25rem; font-weight: 600; color: #fff; letter-spacing: -0.025em;
}
.brand-content {
  position: relative; z-index: 10;
  flex: 1; display: flex; flex-direction: column; justify-content: center;
}
/* text-5xl + leading-tight: gerarchia forte, tecnica consolidata per hero su sfondo scuro */
.brand-heading {
  font-size: 3.25rem; font-weight: 700; color: #fff;
  line-height: 1.15; margin-bottom: 1.25rem; letter-spacing: -0.03em;
}
.brand-sub {
  font-size: 1.0625rem; color: #c7d2fe; line-height: 1.7; max-width: 22rem;
}
.brand-copy {
  position: relative; z-index: 10;
  font-size: 0.8125rem; color: #818cf8;
}

/* Form panel */
.form-panel {
  flex: 1; display: flex; align-items: center; justify-content: center;
  background: #fff;
}
.form-container { width: 100%; max-width: 26rem; padding: 3rem 2.5rem; }

.mobile-logo {
  display: none; text-align: center; margin-bottom: 2.5rem;
  font-size: 1.5rem; font-weight: 700; color: #4f46e5; letter-spacing: -0.025em;
}
@media (max-width: 1023px) { .mobile-logo { display: block; } }

/* Header: mb-10 crea respiro visivo pre-form */
.form-header { margin-bottom: 2.5rem; }
/* text-3xl più autorevole, letter-spacing negativo per titoli grandi */
.form-title { font-size: 1.875rem; font-weight: 700; color: #111827; letter-spacing: -0.03em; }
.form-subtitle { font-size: 0.9375rem; color: #6b7280; margin-top: 0.375rem; }

/* Form body: gap uniforme tra field group */
.form-body { display: flex; flex-direction: column; gap: 1.25rem; }

/* Field group */
.field { display: flex; flex-direction: column; }
.field-label {
  font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;
}

/*
  input-wrap: il border sta sul wrapper, non sull'input.
  Questo permette di usare :focus-within per cambiare lo stile dell'intera riga.
  border-radius 0.75rem (12px): moderno ma non tondo.
*/
.input-wrap {
  display: flex; align-items: center; gap: 0.75rem;
  border: 1.5px solid #d1d5db;
  border-radius: 0.75rem;
  padding: 0 1rem;
  background: #fff;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.input-wrap:focus-within {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.input-icon { width: 1.125rem; height: 1.125rem; color: #9ca3af; flex-shrink: 0; }

/*
  font-size: 1rem (16px) — previene lo zoom automatico di iOS Safari su input.
  padding: 1rem 0 — ~56px di altezza totale, standard SaaS moderno (Stripe, Vercel).
*/
.input-field {
  flex: 1; background: transparent; border: none; outline: none;
  font-size: 1rem; color: #111827;
  padding: 1rem 0;
}
.input-field::placeholder { color: #9ca3af; }

/* Errore */
.error-box {
  font-size: 0.875rem; color: #dc2626;
  background: #fef2f2; border: 1px solid #fecaca;
  border-radius: 0.75rem; padding: 0.75rem 1rem;
}

/*
  submit-btn: altezza allineata agli input (56px) → coerenza verticale.
  margin-top: 2rem — Gestalt proximity, separa visivamente form da CTA.
*/
.submit-btn {
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  width: 100%; margin-top: 2rem;
  padding: 1rem;
  background: #4f46e5; color: #fff;
  font-size: 1rem; font-weight: 600;
  border: none; border-radius: 0.75rem;
  cursor: pointer; transition: background 0.15s;
}
.submit-btn:hover { background: #4338ca; }
.submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-icon { width: 1rem; height: 1rem; }

/* Footer link */
.form-footer {
  text-align: center; margin-top: 2rem;
  font-size: 0.875rem; color: #6b7280;
}
.form-link {
  font-weight: 600; color: #4f46e5; margin-left: 0.25rem; text-decoration: none;
}
.form-link:hover { text-decoration: underline; }
</style>
