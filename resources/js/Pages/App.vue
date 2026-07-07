<template>
  <div class="min-h-screen bg-dark-950">
    <RouterView />
    <CookieConsent />
  </div>
</template>

<script setup lang="ts">
import CookieConsent from '@/components/ui/CookieConsent.vue'
</script>
