import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/login',    name: 'login',    component: () => import('@/views/LoginView.vue'),    meta: { guest: true } },
  { path: '/register', name: 'register', component: () => import('@/views/RegisterView.vue'), meta: { guest: true } },
  {
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { auth: true },
    children: [
      { path: '',          name: 'dashboard', component: () => import('@/views/DashboardView.vue') },
      { path: 'clients',   name: 'clients',   component: () => import('@/views/ClientsView.vue') },
      { path: 'invoices',  name: 'invoices',  component: () => import('@/views/InvoicesView.vue') },
      { path: 'invoices/new',        name: 'invoice-new',    component: () => import('@/views/InvoiceFormView.vue') },
      { path: 'invoices/:id/edit',   name: 'invoice-edit',   component: () => import('@/views/InvoiceFormView.vue') },
      { path: 'invoices/:id',        name: 'invoice-detail', component: () => import('@/views/InvoiceDetailView.vue') },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (!auth.user && auth.token) await auth.fetchMe()

  if (to.meta.auth && !auth.isLoggedIn) return { name: 'login' }
  if (to.meta.guest && auth.isLoggedIn) return { name: 'dashboard' }
})

export default router
