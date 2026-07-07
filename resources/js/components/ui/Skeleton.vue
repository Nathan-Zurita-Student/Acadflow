<template>
  <div class="skeleton" :class="rounded" :style="style" />
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  /** Largura CSS (ex.: '100%', '8rem', '120px'). */
  w?: string
  /** Altura CSS (ex.: '1rem'). */
  h?: string
  /** Renderiza como círculo (usa `w` para os dois lados). */
  circle?: boolean
  /** Classe de arredondamento Tailwind para sobrescrever o padrão. */
  rounded?: string
}>(), { h: '1rem' })

const style = computed(() => ({
  width: props.w ?? '100%',
  height: props.circle ? (props.w ?? props.h) : props.h,
  borderRadius: props.circle ? '9999px' : undefined,
}))

const rounded = computed(() => props.rounded ?? '')
</script>
