<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-dark-950 gap-4">
    <div class="w-8 h-8 border-2 border-accent-500/30 border-t-accent-500 rounded-full animate-spin" />
    <p class="text-dark-400 text-sm">Entrando...</p>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

onMounted(async () => {
  const token = route.query.token as string | undefined
  if (!token || route.query.error) {
    router.replace('/login?error=google')
    return
  }
  auth.setToken(token)
  await auth.fetchMe()
  if (auth.user) router.replace('/')
  else router.replace('/login?error=google')
})
</script>
