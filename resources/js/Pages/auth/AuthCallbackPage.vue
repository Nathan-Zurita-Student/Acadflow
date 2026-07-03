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

// O login com Google agora estabelece a sessão no servidor e redireciona para
// a raiz — sem token na URL. Esta rota é mantida por compatibilidade: hidrata a
// sessão e encaminha o usuário.
onMounted(async () => {
  if (route.query.error) {
    router.replace('/login?error=google')
    return
  }
  await auth.fetchMe()
  router.replace(auth.isAuthenticated ? '/' : '/login?error=google')
})
</script>
