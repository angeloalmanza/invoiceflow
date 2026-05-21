<script setup>
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { LayoutDashboard, Users, FileText, LogOut } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

function handleLogout() {
  auth.logout()
  router.push('/login')
}

const navItems = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/clients', label: 'Clienti', icon: Users },
  { to: '/invoices', label: 'Fatture', icon: FileText },
]

function isActive(path) {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <!-- Logo: brand identity sempre visibile -->
      <div class="sidebar-logo">
        <span class="sidebar-logo-text">InvoiceFlow</span>
      </div>

      <!-- Nav: icona + label, active state con overlay bianco -->
      <nav class="sidebar-nav">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="nav-item"
          :class="isActive(item.to) ? 'nav-item--active' : ''"
        >
          <component :is="item.icon" class="nav-icon" />
          {{ item.label }}
        </RouterLink>
      </nav>

      <!-- User section: avatar con iniziale, truncate su email lunga -->
      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">
            {{ auth.user?.name?.charAt(0).toUpperCase() }}
          </div>
          <div class="user-meta">
            <span class="user-name">{{ auth.user?.name }}</span>
            <span class="user-email">{{ auth.user?.email }}</span>
          </div>
        </div>
        <button @click="handleLogout" class="logout-btn">
          <LogOut class="nav-icon" />
          Esci
        </button>
      </div>
    </aside>

    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-shell { display: flex; min-height: 100vh; }

/* Sidebar dark: massimo contrasto col contenuto chiaro, pattern consolidato SaaS */
.sidebar {
  width: 15rem;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: #0f172a;
}

.sidebar-logo {
  height: 4rem;
  display: flex;
  align-items: center;
  padding: 0 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.sidebar-logo-text {
  font-size: 1.125rem;
  font-weight: 700;
  color: #fff;
  letter-spacing: -0.025em;
}

.sidebar-nav {
  flex: 1;
  padding: 1.25rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

/* Nav item: 44px min height per accessibilità touch target */
.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #94a3b8;
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
}
.nav-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
.nav-item--active { background: rgba(99,102,241,0.2); color: #a5b4fc; }
.nav-icon { width: 1.125rem; height: 1.125rem; flex-shrink: 0; }

.sidebar-footer {
  padding: 1rem 0.75rem;
  border-top: 1px solid rgba(255,255,255,0.07);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.875rem;
}
/* Avatar con colore brand: coerenza visiva con l'accento indigo */
.user-avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: #4f46e5;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #fff;
  flex-shrink: 0;
}
.user-meta { display: flex; flex-direction: column; min-width: 0; }
.user-name { font-size: 0.8125rem; font-weight: 500; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-email { font-size: 0.75rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.logout-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #94a3b8;
  background: transparent;
  border: none;
  cursor: pointer;
  width: 100%;
  transition: background 0.15s, color 0.15s;
}
.logout-btn:hover { background: rgba(239,68,68,0.1); color: #f87171; }

/* Main content: scrollabile indipendentemente dalla sidebar */
.main-content { flex: 1; overflow-y: auto; background: #f8fafc; }
</style>
