<template>
  <Teleport to="body">
    <Transition name="confirm-fade">
      <div v-if="state.open"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        @click.self="cancel">
        <div class="w-full max-w-sm bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl animate-slide-up overflow-hidden"
          role="alertdialog" aria-modal="true">
          <div class="p-6">
            <div class="flex items-start gap-4">
              <!-- Ícone -->
              <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                isDanger ? 'bg-red-500/15 text-red-400' : 'bg-accent-600/20 text-accent-400']">
                <svg v-if="isDanger" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>

              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-white">{{ state.title }}</h3>
                <p class="text-sm text-dark-300 mt-1 leading-relaxed">{{ state.message }}</p>
              </div>
            </div>

            <div class="flex gap-3 mt-6">
              <button ref="cancelBtn" type="button" class="btn-secondary flex-1 justify-center" @click="cancel">
                {{ state.cancelText }}
              </button>
              <button type="button" class="flex-1 justify-center inline-flex items-center gap-2 px-4 py-2 text-white text-sm font-medium rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-dark-900"
                :class="isDanger
                  ? 'bg-red-600 hover:bg-red-500 focus:ring-red-500'
                  : 'bg-accent-600 hover:bg-accent-500 focus:ring-accent-500'"
                @click="accept">
                {{ state.confirmText }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useConfirm } from '@/composables/useConfirm'

const { state, accept, cancel } = useConfirm()
const isDanger = computed(() => state.value.variant === 'danger')
const cancelBtn = ref<HTMLButtonElement | null>(null)

function onKeydown(e: KeyboardEvent) {
  if (!state.value.open) return
  if (e.key === 'Escape') { e.preventDefault(); cancel() }
  else if (e.key === 'Enter') { e.preventDefault(); accept() }
}

watch(() => state.value.open, (open) => {
  if (open) {
    window.addEventListener('keydown', onKeydown)
    // Foca o "Cancelar" por segurança (ação não destrutiva por padrão).
    nextTick(() => cancelBtn.value?.focus())
  } else {
    window.removeEventListener('keydown', onKeydown)
  }
})
</script>

<style scoped>
.confirm-fade-enter-active,
.confirm-fade-leave-active { transition: opacity 0.15s ease; }
.confirm-fade-enter-from,
.confirm-fade-leave-to { opacity: 0; }
</style>
