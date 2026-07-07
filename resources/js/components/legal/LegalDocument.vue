<template>
  <div class="relative min-h-screen w-full overflow-hidden bg-dark-950 text-dark-100">
    <AuthBackground />

    <div class="relative z-10 mx-auto flex min-h-screen max-w-3xl flex-col px-4 py-10 sm:px-6">
      <!-- Header -->
      <header class="mb-8 flex items-center justify-between animate-rise">
        <RouterLink to="/" class="flex items-center gap-2.5">
          <BrandMark size="sm" />
          <span class="text-base font-semibold tracking-tight text-white">AcadFlow</span>
        </RouterLink>
        <RouterLink to="/" class="group inline-flex items-center gap-1.5 text-sm text-dark-400 transition-colors hover:text-dark-100">
          <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
          Voltar
        </RouterLink>
      </header>

      <article class="glass rounded-2xl p-6 sm:p-10 animate-scale-in">
        <div class="mb-8 border-b border-dark-700/70 pb-6">
          <h1 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ title }}</h1>
          <p class="mt-2 text-sm text-dark-500">Última atualização: {{ updatedAt }}</p>
        </div>

        <div class="legal-prose">
          <slot />
        </div>
      </article>

      <p class="mt-6 text-center text-xs text-dark-600">© {{ year }} AcadFlow</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import AuthBackground from '@/components/auth/AuthBackground.vue'
import BrandMark from '@/components/auth/BrandMark.vue'

defineProps<{ title: string; updatedAt: string }>()
const year = new Date().getFullYear()
</script>

<style scoped>
.legal-prose :deep(h2) {
  margin-top: 2rem;
  margin-bottom: 0.65rem;
  font-size: 1.05rem;
  font-weight: 600;
  color: rgb(var(--d-50));
}
.legal-prose :deep(h2:first-child) { margin-top: 0; }
.legal-prose :deep(p) {
  margin-bottom: 0.9rem;
  font-size: 0.925rem;
  line-height: 1.7;
  color: rgb(var(--d-300));
}
.legal-prose :deep(ul) {
  margin: 0 0 1rem 0;
  padding-left: 1.1rem;
  list-style: none;
}
.legal-prose :deep(li) {
  position: relative;
  margin-bottom: 0.5rem;
  padding-left: 1rem;
  font-size: 0.925rem;
  line-height: 1.65;
  color: rgb(var(--d-300));
}
.legal-prose :deep(li::before) {
  content: '';
  position: absolute;
  left: 0;
  top: 0.6rem;
  width: 5px;
  height: 5px;
  border-radius: 9999px;
  background: rgb(var(--accent-400));
}
.legal-prose :deep(a) { color: rgb(var(--accent-400)); font-weight: 500; }
.legal-prose :deep(strong) { color: rgb(var(--d-100)); font-weight: 600; }
</style>
