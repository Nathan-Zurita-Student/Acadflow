<template>
  <span class="material-symbols-outlined" :style="{ fontSize: `${size}px` }" aria-hidden="true">{{ name }}</span>
</template>

<script setup lang="ts">
// Ícone profissional (Material Symbols, carregado via CDN no app.blade.php).
// Uso: <Icon name="notifications" :size="18" class="text-accent-400" />
withDefaults(defineProps<{ name: string; size?: number }>(), { size: 20 })
</script>
