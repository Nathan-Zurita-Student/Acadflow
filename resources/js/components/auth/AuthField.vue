<template>
  <div class="auth-field">
    <label v-if="label" :for="fieldId" class="mb-1.5 block text-[13px] font-medium text-dark-300">
      {{ label }}
    </label>

    <div class="relative">
      <!-- Ícone à esquerda — muda de cor conforme foco/estado -->
      <span
        class="pointer-events-none absolute left-3.5 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center transition-colors duration-200"
        :class="iconColor"
        v-html="icons[icon] ?? ''"
      />

      <input
        :id="fieldId"
        ref="inputEl"
        :type="inputType"
        :value="modelValue"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :inputmode="inputmode"
        class="input-premium"
        :class="[
          { 'state-error': !!error, 'state-success': success && !error, 'pr-11': hasTrailing },
        ]"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        @focus="focused = true"
        @blur="focused = false; $emit('blur')"
        @keydown.enter="$emit('enter')"
      />

      <!-- Trailing: toggle de senha OU check de sucesso -->
      <button
        v-if="type === 'password'"
        type="button"
        tabindex="-1"
        class="trailing-btn"
        :aria-label="show ? 'Ocultar senha' : 'Mostrar senha'"
        @click="show = !show"
      >
        <svg v-if="show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22M6.61 6.61A18.5 18.5 0 0 0 2 12s3 8 10 8a9.12 9.12 0 0 0 5.39-1.61"/></svg>
        <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s3-8 11-8 11 8 11 8-3 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>

      <transition name="pop">
        <span
          v-if="type !== 'password' && success && !error"
          class="trailing-icon text-emerald-400"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        </span>
      </transition>
    </div>

    <!-- Mensagem de erro / dica — altura reservada para não "pular" o layout -->
    <div class="mt-1.5 min-h-[1.05rem] px-0.5">
      <transition name="msg" mode="out-in">
        <p v-if="error" key="err" class="flex items-center gap-1.5 text-[12.5px] font-medium text-red-400">
          <svg class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          {{ error }}
        </p>
        <p v-else-if="hint" key="hint" class="text-[12px] text-dark-500">{{ hint }}</p>
      </transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, useId } from 'vue'

type IconName = 'mail' | 'lock' | 'user' | 'at'

const props = withDefaults(defineProps<{
  modelValue: string
  label?: string
  type?: string
  icon?: IconName
  placeholder?: string
  autocomplete?: string
  inputmode?: 'text' | 'email' | 'numeric' | 'tel' | 'search' | 'url' | 'none' | 'decimal'
  error?: string
  success?: boolean
  hint?: string
}>(), { type: 'text', icon: 'mail', success: false })

defineEmits<{
  (e: 'update:modelValue', v: string): void
  (e: 'blur'): void
  (e: 'enter'): void
}>()

const fieldId = useId()
const inputEl = ref<HTMLInputElement | null>(null)
const focused = ref(false)
const show = ref(false)

const inputType = computed(() => (props.type === 'password' ? (show.value ? 'text' : 'password') : props.type))
const hasTrailing = computed(() => props.type === 'password' || (props.success && !props.error))

const iconColor = computed(() => {
  if (props.error) return 'text-red-400'
  if (props.success) return 'text-emerald-400'
  return focused.value ? 'text-accent-400' : 'text-dark-500'
})

defineExpose({ focus: () => inputEl.value?.focus() })

const icons: Record<IconName, string> = {
  mail: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>`,
  lock: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>`,
  user: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
  at: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>`,
}
</script>

<style scoped>
.trailing-btn,
.trailing-icon {
  position: absolute;
  right: 0.6rem;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: rgb(var(--d-500));
}
.trailing-btn {
  padding: 0.25rem;
  border-radius: 0.5rem;
  transition: color 0.2s, background 0.2s;
}
.trailing-btn:hover { color: rgb(var(--d-200)); background: rgb(var(--d-700) / 0.5); }

/* Estados de validação — sobrepõem o glow accent padrão do .input-premium */
.state-error { border-color: rgb(239 68 68 / 0.7) !important; }
.state-error:focus {
  border-color: rgb(239 68 68) !important;
  box-shadow: 0 0 0 4px rgb(239 68 68 / 0.16) !important;
}
.state-success { border-color: rgb(16 185 129 / 0.55) !important; }
.state-success:focus {
  border-color: rgb(16 185 129) !important;
  box-shadow: 0 0 0 4px rgb(16 185 129 / 0.15) !important;
}

/* Transição da mensagem */
.msg-enter-active, .msg-leave-active { transition: opacity 0.2s, transform 0.2s; }
.msg-enter-from { opacity: 0; transform: translateY(-3px); }
.msg-leave-to  { opacity: 0; transform: translateY(-3px); }

/* Pop do check de sucesso */
.pop-enter-active { transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s; }
.pop-enter-from { opacity: 0; transform: translateY(-50%) scale(0.4); }
</style>
