<template>
  <div class="flex flex-col items-center justify-center px-6 py-14 text-center animate-rise">
    <!-- Ilustração Lottie (LottieFiles) quando fornecida; senão, ícone com glow. -->
    <LottieIcon v-if="lottie" :animation-data="lottie" :size="lottieSize" :loop="loopLottie" class="-mb-2" />
    <div v-else class="empty-icon">
      <span class="empty-glow" aria-hidden="true" />
      <slot name="icon">
        <svg class="relative h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 7l2-3h14l2 3M3 7v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7M3 7h18M9 11a3 3 0 0 0 6 0" />
        </svg>
      </slot>
    </div>

    <h3 class="mt-5 text-base font-semibold text-dark-100">{{ title }}</h3>
    <p v-if="description" class="mt-1.5 max-w-sm text-sm leading-relaxed text-dark-400">{{ description }}</p>

    <div v-if="$slots.action" class="mt-6">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineAsyncComponent } from 'vue'

// Assíncrono: o lottie-web só é baixado quando um Lottie é de fato exibido.
const LottieIcon = defineAsyncComponent(() => import('./LottieIcon.vue'))

withDefaults(defineProps<{
  title: string
  description?: string
  /** Objeto JSON de uma animação Lottie (LottieFiles) para ilustrar o estado. */
  lottie?: object
  lottieSize?: number
  loopLottie?: boolean
}>(), { lottieSize: 150, loopLottie: false })

defineSlots<{ icon?: () => any; action?: () => any }>()
</script>

<style scoped>
.empty-icon {
  position: relative;
  display: inline-flex;
  height: 4rem;
  width: 4rem;
  align-items: center;
  justify-content: center;
  border-radius: 1.25rem;
  color: rgb(var(--accent-300));
  background: linear-gradient(160deg, rgb(var(--accent-500) / 0.16), rgb(var(--accent-500) / 0.04));
  border: 1px solid rgb(var(--accent-500) / 0.2);
  animation: floatY 6s ease-in-out infinite;
  will-change: transform;
}
.empty-glow {
  position: absolute;
  inset: -0.5rem;
  border-radius: 9999px;
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.28), transparent 70%);
  filter: blur(14px);
  opacity: 0.7;
}
@media (prefers-reduced-motion: reduce) {
  .empty-icon { animation: none; }
}
</style>
