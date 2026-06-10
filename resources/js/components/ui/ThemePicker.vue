<template>
  <div class="relative">
    <!-- Sidebar expandida: exibe os swatches inline -->
    <template v-if="!collapsed">
      <div class="px-3 py-2">
        <p class="text-[10px] font-semibold text-dark-500 uppercase tracking-wider mb-2">Tema</p>
        <div class="flex items-center gap-1.5">
          <button
            v-for="t in THEMES"
            :key="t.name"
            @click="themeStore.setTheme(t.name)"
            :title="t.label + ' — ' + t.description"
            class="w-6 h-6 rounded-full border-2 transition-all duration-150 hover:scale-110 focus:outline-none"
            :style="{ background: t.preview }"
            :class="themeStore.current === t.name
              ? 'border-indigo-400 scale-110 shadow-lg'
              : 'border-dark-600 hover:border-dark-400'"
          />
        </div>
      </div>
    </template>

    <!-- Sidebar colapsada: ícone de paleta com popover -->
    <template v-else>
      <div ref="containerRef" class="px-2">
        <button
          @click="open = !open"
          title="Temas"
          class="w-full flex justify-center p-2 rounded-lg text-dark-400 hover:text-dark-200 hover:bg-dark-800 transition-colors"
        >
          <!-- palette icon -->
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="13.5" cy="6.5" r=".5" fill="currentColor" />
            <circle cx="17.5" cy="10.5" r=".5" fill="currentColor" />
            <circle cx="8.5"  cy="7.5"  r=".5" fill="currentColor" />
            <circle cx="6.5"  cy="12.5" r=".5" fill="currentColor" />
            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z" />
          </svg>
        </button>

        <!-- Popover flutuante -->
        <Transition name="popup">
          <div
            v-if="open"
            class="absolute left-full ml-2 bottom-0 z-50 bg-dark-800 border border-dark-700 rounded-xl p-3 shadow-2xl w-44"
          >
            <p class="text-xs font-semibold text-dark-400 mb-2">Tema</p>
            <div class="space-y-1">
              <button
                v-for="t in THEMES"
                :key="t.name"
                @click="selectAndClose(t.name)"
                class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg transition-colors hover:bg-dark-700"
                :class="themeStore.current === t.name ? 'bg-dark-700' : ''"
              >
                <span class="w-4 h-4 rounded-full border-2 flex-shrink-0"
                  :style="{ background: t.preview }"
                  :class="themeStore.current === t.name ? 'border-indigo-400' : 'border-dark-600'" />
                <span class="text-xs" :class="themeStore.current === t.name ? 'text-dark-100 font-medium' : 'text-dark-400'">
                  {{ t.label }}
                </span>
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { useThemeStore, THEMES, type ThemeName } from '@/stores/theme'

defineProps<{ collapsed?: boolean }>()

const themeStore = useThemeStore()
const open = ref(false)
const containerRef = ref<HTMLElement>()

onClickOutside(containerRef, () => { open.value = false })

function selectAndClose(name: ThemeName) {
  themeStore.setTheme(name)
  open.value = false
}
</script>

<style scoped>
.popup-enter-active { animation: slideLeft 0.2s ease-out; }
.popup-leave-active { animation: slideLeft 0.15s ease-in reverse; }
@keyframes slideLeft {
  from { opacity: 0; transform: translateX(-8px); }
  to   { opacity: 1; transform: translateX(0); }
}
</style>
