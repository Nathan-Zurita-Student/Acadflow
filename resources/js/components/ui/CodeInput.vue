<template>
  <div class="flex gap-2 justify-center" @paste="onPaste">
    <input
      v-for="(_, i) in 6"
      :key="i"
      :ref="(el) => setRef(el as HTMLInputElement | null, i)"
      :value="digits[i]"
      type="text"
      inputmode="numeric"
      autocomplete="one-time-code"
      maxlength="1"
      class="w-11 h-14 text-center text-xl font-bold rounded-lg bg-dark-900 border border-dark-700 text-white focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/30 transition-colors"
      :disabled="disabled"
      @input="onInput(i, $event)"
      @keydown="onKeydown(i, $event)"
      @focus="($event.target as HTMLInputElement).select()"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{ modelValue: string; disabled?: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [string]; complete: [string] }>()

const digits = ref<string[]>(Array(6).fill(''))
const inputs = ref<(HTMLInputElement | null)[]>([])

function setRef(el: HTMLInputElement | null, i: number) {
  inputs.value[i] = el
}

function sync() {
  const value = digits.value.join('')
  emit('update:modelValue', value)
  if (value.length === 6) emit('complete', value)
}

function onInput(i: number, e: Event) {
  const raw = (e.target as HTMLInputElement).value.replace(/\D/g, '')
  digits.value[i] = raw.slice(-1)
  // Reflete o valor saneado no input (caso o usuário digite não-número).
  ;(e.target as HTMLInputElement).value = digits.value[i]
  if (digits.value[i] && i < 5) inputs.value[i + 1]?.focus()
  sync()
}

function onKeydown(i: number, e: KeyboardEvent) {
  if (e.key === 'Backspace' && !digits.value[i] && i > 0) {
    inputs.value[i - 1]?.focus()
  } else if (e.key === 'ArrowLeft' && i > 0) {
    inputs.value[i - 1]?.focus()
  } else if (e.key === 'ArrowRight' && i < 5) {
    inputs.value[i + 1]?.focus()
  }
}

function onPaste(e: ClipboardEvent) {
  e.preventDefault()
  const text = (e.clipboardData?.getData('text') ?? '').replace(/\D/g, '').slice(0, 6)
  if (!text) return
  for (let i = 0; i < 6; i++) digits.value[i] = text[i] ?? ''
  inputs.value[Math.min(text.length, 5)]?.focus()
  sync()
}

// Permite ao pai limpar o campo (ex.: após erro) via v-model = ''.
watch(() => props.modelValue, (value) => {
  if (!value) {
    digits.value = Array(6).fill('')
    inputs.value[0]?.focus()
  }
})
</script>
