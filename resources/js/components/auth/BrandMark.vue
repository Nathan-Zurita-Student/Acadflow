<template>
  <div class="brand-mark group" :class="sizeMap[size].wrap">
    <!-- Glow pulsante atrás do tile -->
    <span class="mark-glow" :class="sizeMap[size].glow" aria-hidden="true" />

    <!-- Tile com gradiente + borda luminosa + sheen -->
    <span
      class="mark-tile border-gradient"
      :class="sizeMap[size].tile"
    >
      <span class="mark-sheen" aria-hidden="true" />
      <!-- Capelo (graduation cap) — flutua suavemente -->
      <svg
        class="mark-cap relative z-10 text-white"
        :class="sizeMap[size].icon"
        viewBox="0 0 32 32"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        <path d="M16 6L2 13l14 7 14-7-14-7z" fill="currentColor" fill-opacity="0.97" />
        <path d="M8 17v6c0 0 3.5 3 8 3s8-3 8-3v-6l-8 4-8-4z" fill="currentColor" fill-opacity="0.78" />
        <path d="M26 13v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        <circle cx="26" cy="21.5" r="1.5" fill="currentColor" fill-opacity="0.85" />
      </svg>
    </span>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' })

const sizeMap = {
  sm: { wrap: 'w-10 h-10', tile: 'rounded-xl',  icon: 'w-6 h-6',  glow: 'inset-0' },
  md: { wrap: 'w-14 h-14', tile: 'rounded-2xl', icon: 'w-8 h-8',  glow: '-inset-1' },
  lg: { wrap: 'w-16 h-16', tile: 'rounded-2xl', icon: 'w-9 h-9',  glow: '-inset-2' },
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
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.75), transparent 70%);
  filter: blur(14px);
  opacity: 0.55;
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
  background: linear-gradient(145deg, rgb(var(--accent-500)), rgb(var(--accent-700)) 90%);
  box-shadow:
    0 10px 30px -8px rgb(var(--accent-600) / 0.6),
    inset 0 1px 0 0 rgb(255 255 255 / 0.25);
  transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.group:hover .mark-tile {
  transform: scale(1.06) rotate(-3deg);
}

/* Reflexo diagonal que cruza o tile periodicamente */
.mark-sheen {
  position: absolute;
  inset: 0;
  background: linear-gradient(110deg, transparent 30%, rgb(255 255 255 / 0.35) 50%, transparent 70%);
  transform: translateX(-120%);
  animation: shimmer 4.5s ease-in-out infinite;
}

.mark-cap {
  animation: floatY 6s ease-in-out infinite;
  will-change: transform;
}
.group:hover .mark-cap {
  animation-play-state: paused;
}

@media (prefers-reduced-motion: reduce) {
  .mark-glow, .mark-sheen, .mark-cap { animation: none; }
}
</style>
