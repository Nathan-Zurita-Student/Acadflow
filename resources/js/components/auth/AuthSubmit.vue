<template>
  <button
    :type="type"
    :disabled="disabled || loading || success"
    class="auth-submit"
    :class="{ 'is-success': success }"
    @pointerdown="spawnRipple"
  >
    <!-- Ripples -->
    <span class="pointer-events-none absolute inset-0 overflow-hidden rounded-xl">
      <span v-for="r in ripples" :key="r.id" class="ripple" :style="r.style" />
    </span>

    <!-- Sheen no hover -->
    <span class="sheen" aria-hidden="true" />

    <span class="relative z-10 flex items-center justify-center gap-2">
      <transition name="swap" mode="out-in">
        <!-- Sucesso -->
        <span v-if="success" key="ok" class="flex items-center gap-2">
          <svg class="h-5 w-5 check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          <slot name="success">Pronto!</slot>
        </span>

        <!-- Loading -->
        <span v-else-if="loading" key="load" class="flex items-center gap-2">
          <span class="spinner" />
          <slot name="loading">Aguarde…</slot>
        </span>

        <!-- Idle -->
        <span v-else key="idle" class="flex items-center gap-2">
          <slot />
          <svg v-if="arrow" class="arrow h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
      </transition>
    </span>
  </button>
</template>

<script setup lang="ts">
import { ref } from 'vue'

withDefaults(defineProps<{
  loading?: boolean
  success?: boolean
  disabled?: boolean
  type?: 'submit' | 'button'
  arrow?: boolean
}>(), { loading: false, success: false, disabled: false, type: 'submit', arrow: true })

interface Ripple { id: number; style: Record<string, string> }
const ripples = ref<Ripple[]>([])
let rid = 0

function spawnRipple(e: PointerEvent) {
  const el = e.currentTarget as HTMLElement
  const rect = el.getBoundingClientRect()
  const size = Math.max(rect.width, rect.height)
  const id = ++rid
  ripples.value.push({
    id,
    style: {
      width: `${size}px`,
      height: `${size}px`,
      left: `${e.clientX - rect.left - size / 2}px`,
      top: `${e.clientY - rect.top - size / 2}px`,
    },
  })
  window.setTimeout(() => {
    ripples.value = ripples.value.filter((r) => r.id !== id)
  }, 650)
}
</script>

<style scoped>
.auth-submit {
  position: relative;
  width: 100%;
  overflow: hidden;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.7rem 1rem;
  border-radius: 0.75rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(180deg, rgb(var(--accent-500)), rgb(var(--accent-600)));
  box-shadow:
    0 8px 24px -8px rgb(var(--accent-600) / 0.6),
    inset 0 1px 0 0 rgb(255 255 255 / 0.2);
  transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1),
              box-shadow 0.25s ease, filter 0.2s ease, background 0.3s ease;
}
.auth-submit:hover:not(:disabled) {
  filter: brightness(1.06);
  box-shadow:
    0 12px 34px -8px rgb(var(--accent-600) / 0.75),
    inset 0 1px 0 0 rgb(255 255 255 / 0.25);
}
.auth-submit:active:not(:disabled) { transform: scale(0.97); }
.auth-submit:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgb(var(--d-950)), 0 0 0 5px rgb(var(--accent-400));
}
.auth-submit:disabled { cursor: not-allowed; }
.auth-submit.is-success {
  background: linear-gradient(180deg, rgb(16 185 129), rgb(5 150 105));
  box-shadow: 0 8px 24px -8px rgb(16 185 129 / 0.6);
  opacity: 1;
}

.sheen {
  position: absolute;
  inset: 0;
  background: linear-gradient(110deg, transparent 35%, rgb(255 255 255 / 0.22) 50%, transparent 65%);
  transform: translateX(-130%);
  transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
.auth-submit:hover:not(:disabled) .sheen { transform: translateX(130%); }

.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgb(255 255 255 / 0.35);
  border-top-color: #fff;
  border-radius: 9999px;
  animation: spin 0.7s linear infinite;
}
.arrow { transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
.auth-submit:hover:not(:disabled) .arrow { transform: translateX(3px); }
.check { animation: pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }

@keyframes pop { from { transform: scale(0.3); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.swap-enter-active, .swap-leave-active { transition: opacity 0.18s, transform 0.18s; }
.swap-enter-from { opacity: 0; transform: translateY(4px); }
.swap-leave-to  { opacity: 0; transform: translateY(-4px); }
</style>
