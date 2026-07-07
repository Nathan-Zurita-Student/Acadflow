<template>
  <Teleport to="body">
    <Transition name="sh-fade">
      <div
        v-if="helpOpen"
        class="fixed inset-0 z-[210] flex items-center justify-center px-4"
      >
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @mousedown="closeHelp" />

        <Transition name="sh-pop" appear>
          <div v-if="helpOpen" class="glass border-gradient relative w-full max-w-lg overflow-hidden rounded-2xl shadow-card-float" role="dialog" aria-modal="true">
            <div class="flex items-center justify-between border-b border-dark-700/70 px-5 py-4">
              <h2 class="flex items-center gap-2 text-base font-semibold text-white">
                <svg class="h-5 w-5 text-accent-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h.01M10 8h.01M14 8h.01M18 8h.01M6 12h.01M10 12h.01M14 12h.01M18 12h.01M7 16h10M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/></svg>
                Atalhos de teclado
              </h2>
              <button class="rounded-lg p-1.5 text-dark-400 transition-colors hover:bg-dark-700 hover:text-dark-100" @click="closeHelp">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
              </button>
            </div>

            <div class="max-h-[65vh] overflow-y-auto p-5">
              <div v-for="section in sections" :key="section.title" class="mb-5 last:mb-0">
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-dark-500">{{ section.title }}</p>
                <div class="space-y-1">
                  <div v-for="row in section.rows" :key="row.label" class="flex items-center justify-between rounded-lg px-2 py-2 transition-colors hover:bg-dark-800/60">
                    <span class="text-sm text-dark-200">{{ row.label }}</span>
                    <span class="flex items-center gap-1">
                      <template v-for="(k, i) in row.keys" :key="i">
                        <kbd class="sh-kbd">{{ k }}</kbd>
                        <span v-if="i < row.keys.length - 1" class="text-xs text-dark-600">+</span>
                      </template>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useShortcuts } from '@/composables/useShortcuts'

const { helpOpen, closeHelp } = useShortcuts()

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent)
const mod = isMac ? '⌘' : 'Ctrl'

const sections = computed(() => [
  {
    title: 'Geral',
    rows: [
      { label: 'Busca global', keys: [mod, 'K'] },
      { label: 'Buscar', keys: ['/'] },
      { label: 'Criar (nova tarefa / projeto)', keys: ['N'] },
      { label: 'Mostrar atalhos', keys: ['?'] },
      { label: 'Fechar modais', keys: ['Esc'] },
      { label: 'Salvar (em telas de edição)', keys: [mod, 'S'] },
    ],
  },
  {
    title: 'Navegação',
    rows: [
      { label: 'Ir para Dashboard, Projetos, Calendário…', keys: [mod, 'K'] },
    ],
  },
])
</script>

<style scoped>
.sh-kbd {
  display: inline-flex;
  min-width: 1.5rem;
  align-items: center;
  justify-content: center;
  border-radius: 0.375rem;
  border: 1px solid rgb(var(--d-600));
  border-bottom-width: 2px;
  background: rgb(var(--d-800));
  padding: 0.15rem 0.4rem;
  font-size: 11px;
  font-weight: 600;
  color: rgb(var(--d-200));
}
.sh-fade-enter-active, .sh-fade-leave-active { transition: opacity 0.2s ease; }
.sh-fade-enter-from, .sh-fade-leave-to { opacity: 0; }
.sh-pop-enter-active { transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.28s ease; }
.sh-pop-enter-from { opacity: 0; transform: translateY(-8px) scale(0.97); }
</style>
