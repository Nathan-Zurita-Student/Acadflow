<template>
  <div class="brand-mark group" :class="sizeMap[size].wrap">
    <!-- Brilho suave atrás do logo -->
    <span class="mark-glow" :class="sizeMap[size].glow" aria-hidden="true" />

    <!-- Logo transparente do AcadFlow -->
    <img src="/imagem/acadflow.png" alt="AcadFlow" class="mark-img" />
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' })

const sizeMap = {
  sm: { wrap: 'w-10 h-10', glow: 'inset-0' },
  md: { wrap: 'w-14 h-14', glow: '-inset-1' },
  lg: { wrap: 'w-16 h-16', glow: '-inset-2' },
} as const
</script>

<style scoped>
.brand-mark {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.mark-glow {
  position: absolute;
  border-radius: 9999px;
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.45), transparent 70%);
  filter: blur(13px);
  opacity: 0.45;
  animation: glowPulse 4s ease-in-out infinite;
  will-change: transform, opacity;
}

.mark-img {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.group:hover .mark-img {
  transform: scale(1.08) rotate(-3deg);
}

@media (prefers-reduced-motion: reduce) {
  .mark-glow { animation: none; }
}
</style>
