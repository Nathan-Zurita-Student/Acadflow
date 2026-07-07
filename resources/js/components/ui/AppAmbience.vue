<template>
  <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    <span class="amb-orb amb-1 animate-drift" />
    <span class="amb-orb amb-2 animate-drift-rev" />
    <span class="amb-grid" />
  </div>
</template>

<style scoped>
/* Orbs muito sutis — dão "vida" sem atrapalhar a leitura do conteúdo. */
.amb-orb {
  position: absolute;
  border-radius: 9999px;
  filter: blur(72px);
  mix-blend-mode: screen;
  will-change: transform;
}
.amb-1 {
  top: -14%;
  left: -8%;
  width: 42vw;
  height: 42vw;
  max-width: 620px;
  max-height: 620px;
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.16), transparent 70%);
}
.amb-2 {
  bottom: -18%;
  right: -10%;
  width: 46vw;
  height: 46vw;
  max-width: 680px;
  max-height: 680px;
  background: radial-gradient(circle, rgb(139 92 246 / 0.14), transparent 70%);
}
/* Grade tecnológica quase imperceptível, com fade radial. */
.amb-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(to right, rgb(var(--d-500) / 0.14) 1px, transparent 1px),
    linear-gradient(to bottom, rgb(var(--d-500) / 0.14) 1px, transparent 1px);
  background-size: 52px 52px;
  -webkit-mask-image: radial-gradient(ellipse 90% 70% at 50% 0%, #000 0%, transparent 70%);
          mask-image: radial-gradient(ellipse 90% 70% at 50% 0%, #000 0%, transparent 70%);
  opacity: 0.4;
}
@media (prefers-reduced-motion: reduce) {
  .amb-orb { animation: none !important; }
}
</style>
