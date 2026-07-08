<template>
  <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    <!-- Orbs de gradiente (mesh) em movimento -->
    <span class="orb orb-1" />
    <span class="orb orb-2" />
    <span class="orb orb-3" />
    <span class="orb orb-4" />
    <span class="orb orb-5" />
    <span class="orb orb-6" />
    <span class="orb orb-7" />
    <span class="orb orb-8" />
    <!-- Aurora superior -->
    <span class="aurora animate-aurora" />
    <!-- Grid tecnológico com fade radial -->
    <span class="tech-grid animate-grid-pan" />
    <!-- Vinheta para focar o centro -->
    <span class="vignette" />
    <!-- Ruído sutil (textura premium) -->
    <span class="noise" />
  </div>
</template>

<style scoped>
.orb {
  position: absolute;
  border-radius: 9999px;
  filter: blur(80px);
  mix-blend-mode: screen;
  will-change: transform;
}
.orb-1 {
  top: -12%; left: -8%;
  width: 46vw; height: 46vw; max-width: 640px; max-height: 640px;
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.55), transparent 68%);
  animation: orbDriftA 5s ease-in-out infinite;
}
.orb-2 {
  bottom: -18%; right: -10%;
  width: 50vw; height: 50vw; max-width: 680px; max-height: 680px;
  background: radial-gradient(circle, rgb(139 92 246 / 0.5), transparent 68%);
  animation: orbDriftB 5s ease-in-out infinite;
}
.orb-3 {
  top: 32%; left: 38%;
  width: 34vw; height: 34vw; max-width: 460px; max-height: 460px;
  background: radial-gradient(circle, rgb(34 211 238 / 0.32), transparent 70%);
  animation: orbDriftC 5s ease-in-out infinite;
}
.orb-4 {
  top: 52%; left: 2%;
  width: 30vw; height: 30vw; max-width: 420px; max-height: 420px;
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.40), transparent 70%);
  animation: orbDriftB 5s ease-in-out infinite;
  animation-delay: -2s;
}
.orb-5 {
  top: 4%; right: 22%;
  width: 28vw; height: 28vw; max-width: 380px; max-height: 380px;
  background: radial-gradient(circle, rgb(139 92 246 / 0.42), transparent 70%);
  animation: orbDriftC 5s ease-in-out infinite;
  animation-delay: -3.5s;
}
.orb-6 {
  bottom: 2%; left: 44%;
  width: 26vw; height: 26vw; max-width: 360px; max-height: 360px;
  background: radial-gradient(circle, rgb(34 211 238 / 0.30), transparent 72%);
  animation: orbDriftA 5s ease-in-out infinite;
  animation-delay: -1.5s;
}
.orb-7 {
  top: 18%; right: 4%;
  width: 24vw; height: 24vw; max-width: 340px; max-height: 340px;
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.34), transparent 72%);
  animation: orbDriftC 5s ease-in-out infinite;
  animation-delay: -2.6s;
}
.orb-8 {
  bottom: 24%; left: 22%;
  width: 22vw; height: 22vw; max-width: 320px; max-height: 320px;
  background: radial-gradient(circle, rgb(139 92 246 / 0.34), transparent 72%);
  animation: orbDriftB 5s ease-in-out infinite;
  animation-delay: -4s;
}

/* Movimento mais amplo e dinâmico dos orbs (só transform → GPU). */
@keyframes orbDriftA {
  0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
  33%      { transform: translate3d(9%, -11%, 0) scale(1.14); }
  66%      { transform: translate3d(-8%, 7%, 0) scale(0.92); }
}
@keyframes orbDriftB {
  0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
  33%      { transform: translate3d(-10%, 9%, 0) scale(1.12); }
  66%      { transform: translate3d(8%, -7%, 0) scale(0.9); }
}
@keyframes orbDriftC {
  0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
  50%      { transform: translate3d(6%, -14%, 0) scale(1.1); }
}
.aurora {
  position: absolute;
  top: -30%; left: 50%;
  width: 120%; height: 60%;
  transform: translateX(-50%);
  background: conic-gradient(from 120deg at 50% 50%,
    rgb(var(--accent-500) / 0.0),
    rgb(var(--accent-400) / 0.35),
    rgb(139 92 246 / 0.3),
    rgb(34 211 238 / 0.22),
    rgb(var(--accent-500) / 0.0));
  filter: blur(70px);
  opacity: 0.6;
  will-change: transform, opacity;
}
.tech-grid {
  position: absolute;
  inset: -2px;
  background-image:
    linear-gradient(to right, rgb(var(--d-600) / 0.28) 1px, transparent 1px),
    linear-gradient(to bottom, rgb(var(--d-600) / 0.28) 1px, transparent 1px);
  background-size: 46px 46px;
  -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, #000 0%, transparent 75%);
          mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, #000 0%, transparent 75%);
  opacity: 0.5;
  will-change: background-position;
}
.vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 100% 100% at 50% 30%, transparent 40%, rgb(var(--d-950) / 0.85) 100%);
}
.noise {
  position: absolute;
  inset: 0;
  opacity: 0.035;
  mix-blend-mode: overlay;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}
@media (prefers-reduced-motion: reduce) {
  .orb, .aurora, .tech-grid { animation: none !important; }
}
</style>
