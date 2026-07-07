<template>
  <div class="brand-mark group" :class="sizeMap[size].wrap">
    <!-- Glow pulsante atrás do tile -->
    <span class="mark-glow" :class="sizeMap[size].glow" aria-hidden="true" />

    <!-- Tile arredondado com o logo do AcadFlow + reflexo -->
    <span class="mark-tile" :class="sizeMap[size].tile">
      <span class="mark-sheen" aria-hidden="true" />
      <img src="/imagem/acadflow.png" alt="AcadFlow" class="mark-img" />
    </span>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' })

const sizeMap = {
  sm: { wrap: 'w-10 h-10', tile: 'rounded-xl',  glow: 'inset-0' },
  md: { wrap: 'w-14 h-14', tile: 'rounded-2xl', glow: '-inset-1' },
  lg: { wrap: 'w-16 h-16', tile: 'rounded-2xl', glow: '-inset-2' },
} as const
</script>

<style scoped>
.brand-mark {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  /* Flutua o conjunto inteiro (sem revelar bordas da imagem). */
  animation: floatY 7s ease-in-out infinite;
  will-change: transform;
}

.mark-glow {
  position: absolute;
  border-radius: 9999px;
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.6), transparent 70%);
  filter: blur(14px);
  opacity: 0.5;
  animation: glowPulse 4s ease-in-out infinite;
  will-change: transform, opacity;
}

.mark-tile {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background: rgb(var(--d-900));
  box-shadow:
    0 10px 30px -8px rgb(0 0 0 / 0.5),
    inset 0 0 0 1px rgb(255 255 255 / 0.08);
  transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.group:hover .mark-tile {
  transform: scale(1.06) rotate(-3deg);
}

.mark-img {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Reflexo diagonal que cruza o tile periodicamente */
.mark-sheen {
  position: absolute;
  inset: 0;
  z-index: 2;
  background: linear-gradient(110deg, transparent 30%, rgb(255 255 255 / 0.35) 50%, transparent 70%);
  transform: translateX(-120%);
  animation: shimmer 4.5s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
  .brand-mark, .mark-glow, .mark-sheen { animation: none; }
}
</style>
