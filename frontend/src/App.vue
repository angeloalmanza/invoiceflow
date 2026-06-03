<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(true)

router.isReady().then(() => {
  loading.value = false
})
</script>

<template>
  <div v-if="loading" class="boot-screen">
    <div class="boot-spinner"></div>
    <p class="boot-text">Connecting to server...</p>
  </div>
  <RouterView v-else />
</template>

<style>
.boot-screen {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  gap: 1rem;
}
.boot-spinner {
  width: 2rem;
  height: 2rem;
  border: 2.5px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.boot-text {
  font-size: 0.875rem;
  color: #6b7280;
  font-family: system-ui, sans-serif;
}
</style>
