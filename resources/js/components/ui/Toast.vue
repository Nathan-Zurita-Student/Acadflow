<template>
  <Teleport to="body">
    <div class="fixed bottom-6 right-6 z-[300] flex flex-col gap-2 pointer-events-none">
      <TransitionGroup
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 translate-x-8 scale-95"
        enter-to-class="opacity-100 translate-x-0 scale-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-x-0 scale-100"
        leave-to-class="opacity-0 translate-x-8 scale-95"
      >
        <div
          v-for="t in toasts"
          :key="t.id"
          class="flex items-center gap-3 pl-4 pr-3 py-3 rounded-xl shadow-2xl text-sm font-medium pointer-events-auto w-80"
          :class="classes(t.type)"
        >
          <span class="text-lg flex-shrink-0 leading-none">{{ icon(t.type) }}</span>
          <span class="flex-1 leading-snug">{{ t.message }}</span>
          <button @click="dismiss(t.id)"
            class="opacity-50 hover:opacity-100 transition-opacity flex-shrink-0 p-0.5 rounded">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { useToast } from '@/composables/useToast'
const { toasts, dismiss } = useToast()

function icon(type: string) {
  return { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' }[type] ?? '🔔'
}
function classes(type: string) {
  return {
    success: 'bg-emerald-900/90 border border-emerald-700/50 text-emerald-100',
    error:   'bg-red-900/90 border border-red-700/50 text-red-100',
    info:    'bg-accent-900/90 border border-accent-700/50 text-accent-100',
    warning: 'bg-amber-900/90 border border-amber-700/50 text-amber-100',
  }[type] ?? 'bg-dark-700 border border-dark-600 text-dark-100'
}
</script>
