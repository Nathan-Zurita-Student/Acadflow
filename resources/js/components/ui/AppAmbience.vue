<template>
  <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    <span class="amb-orb amb-1" />
    <span class="amb-orb amb-2" />
    <span class="amb-orb amb-3" />
    <span class="amb-orb amb-4" />
    <span class="amb-orb amb-5" />
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
  animation: ambDriftA 5s ease-in-out infinite;
}
.amb-2 {
  bottom: -18%;
  right: -10%;
  width: 46vw;
  height: 46vw;
  max-width: 680px;
  max-height: 680px;
  background: radial-gradient(circle, rgb(139 92 246 / 0.14), transparent 70%);
  animation: ambDriftB 5s ease-in-out infinite;
  animation-delay: -1.5s;
}
.amb-3 {
  top: 38%; left: 34%;
  width: 34vw; height: 34vw; max-width: 480px; max-height: 480px;
  background: radial-gradient(circle, rgb(34 211 238 / 0.12), transparent 72%);
  animation: ambDriftA 5s ease-in-out infinite;
  animation-delay: -3s;
}
.amb-4 {
  top: 2%; right: 24%;
  width: 30vw; height: 30vw; max-width: 420px; max-height: 420px;
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.13), transparent 72%);
  animation: ambDriftB 5s ease-in-out infinite;
  animation-delay: -2.2s;
}
.amb-5 {
  bottom: 4%; left: 40%;
  width: 28vw; height: 28vw; max-width: 400px; max-height: 400px;
  background: radial-gradient(circle, rgb(139 92 246 / 0.12), transparent 72%);
  animation: ambDriftA 5s ease-in-out infinite;
  animation-delay: -4s;
}

@keyframes ambDriftA {
  0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
  33%      { transform: translate3d(6%, -7%, 0) scale(1.08); }
  66%      { transform: translate3d(-5%, 5%, 0) scale(0.95); }
}
@keyframes ambDriftB {
  0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
  50%      { transform: translate3d(-6%, 6%, 0) scale(1.06); }
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
