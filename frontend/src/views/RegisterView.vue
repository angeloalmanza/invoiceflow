<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { User, Mail, Lock, ArrowRight } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Le password non coincidono'
    return
  }
  loading.value = true
  try {
    await auth.register(name.value, email.value, password.value, passwordConfirmation.value)
    router.push('/')
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : 'Registrazione fallita'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">

    <div class="brand-panel">
      <div class="brand-orb brand-orb--tl"></div>
      <div class="brand-orb brand-orb--mr"></div>
      <div class="brand-orb brand-orb--bl"></div>

      <span class="brand-logo">InvoiceFlow</span>

      <div class="brand-content">
        <h2 class="brand-heading">
          Inizia a fatturare<br>in modo semplice<br>e professionale.
        </h2>
        <p class="brand-sub">
          Crea il tuo account gratuitamente<br>e gestisci clienti e fatture.
        </p>
      </div>

      <span class="brand-copy">© 2026 InvoiceFlow</span>
    </div>

    <div class="form-panel">
      <div class="form-container">

        <div class="mobile-logo">InvoiceFlow</div>

        <div class="form-header">
          <h1 class="form-title">Crea il tuo account</h1>
          <p class="form-subtitle">Inizia gratuitamente, nessuna carta richiesta</p>
        </div>

        <form @submit.prevent="submit" class="form-body">

          <div class="field">
            <label class="field-label">Nome</label>
            <div class="input-wrap">
              <User class="input-icon" />
              <input
                v-model="name" type="text" required
                placeholder="Mario Rossi"
                class="input-field"
              />
            </div>
          </div>

          <div class="field">
            <label class="field-label">Email</label>
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
                placeholder="Minimo 8 caratteri"
                class="input-field"
              />
            </div>
          </div>

          <div class="field">
            <label class="field-label">Conferma password</label>
            <div class="input-wrap">
              <Lock class="input-icon" />
              <input
                v-model="passwordConfirmation" type="password" required
                placeholder="••••••••"
                class="input-field"
              />
            </div>
          </div>

          <div v-if="error" class="error-box">{{ error }}</div>

          <button type="submit" :disabled="loading" class="submit-btn">
            <span>{{ loading ? 'Registrazione...' : 'Crea account' }}</span>
            <ArrowRight v-if="!loading" class="btn-icon" />
          </button>
        </form>

        <p class="form-footer">
          Hai già un account?
          <RouterLink to="/login" class="form-link">Accedi</RouterLink>
        </p>

      </div>
    </div>

  </div>
</template>

<style scoped>
.auth-page { display: flex; min-height: 100vh; }

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

.brand-orb { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.05); }
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
.brand-heading {
  font-size: 3.25rem; font-weight: 700; color: #fff;
  line-height: 1.15; margin-bottom: 1.25rem; letter-spacing: -0.03em;
}
.brand-sub { font-size: 1.0625rem; color: #c7d2fe; line-height: 1.7; max-width: 22rem; }
.brand-copy { position: relative; z-index: 10; font-size: 0.8125rem; color: #818cf8; }

.form-panel {
  flex: 1; display: flex; align-items: center; justify-content: center; background: #fff;
}
.form-container { width: 100%; max-width: 26rem; padding: 3rem 2.5rem; }

.mobile-logo {
  display: none; text-align: center; margin-bottom: 2.5rem;
  font-size: 1.5rem; font-weight: 700; color: #4f46e5; letter-spacing: -0.025em;
}
@media (max-width: 1023px) { .mobile-logo { display: block; } }

.form-header { margin-bottom: 2.5rem; }
.form-title { font-size: 1.875rem; font-weight: 700; color: #111827; letter-spacing: -0.03em; }
.form-subtitle { font-size: 0.9375rem; color: #6b7280; margin-top: 0.375rem; }

.form-body { display: flex; flex-direction: column; gap: 1.25rem; }

.field { display: flex; flex-direction: column; }
.field-label { font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem; }

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

.input-field {
  flex: 1; background: transparent; border: none; outline: none;
  font-size: 1rem; color: #111827;
  padding: 1rem 0;
}
.input-field::placeholder { color: #9ca3af; }

.error-box {
  font-size: 0.875rem; color: #dc2626;
  background: #fef2f2; border: 1px solid #fecaca;
  border-radius: 0.75rem; padding: 0.75rem 1rem;
}

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

.form-footer { text-align: center; margin-top: 2rem; font-size: 0.875rem; color: #6b7280; }
.form-link { font-weight: 600; color: #4f46e5; margin-left: 0.25rem; text-decoration: none; }
.form-link:hover { text-decoration: underline; }
</style>
