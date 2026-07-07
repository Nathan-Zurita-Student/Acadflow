<template>
  <div class="relative min-h-screen w-full overflow-hidden bg-dark-950 text-dark-100">
    <!-- ============================ BACKGROUND VIVO ============================ -->
    <AuthBackground />

    <!-- ============================ CONTEÚDO ============================ -->
    <div class="relative z-10 grid min-h-screen w-full lg:grid-cols-[1.05fr_1fr]">
      <!-- Painel de marca — somente desktop -->
      <aside class="relative hidden flex-col justify-between overflow-hidden px-12 py-14 lg:flex xl:px-16">
        <!-- Topo: logo + wordmark -->
        <RouterLink to="/" class="flex items-center gap-3 animate-rise" style="animation-delay: 40ms">
          <BrandMark size="md" />
          <div class="leading-tight">
            <span class="block text-lg font-semibold tracking-tight text-white">AcadFlow</span>
            <span class="block text-xs font-medium text-dark-400">Plataforma acadêmica</span>
          </div>
        </RouterLink>

        <!-- Meio: headline + features -->
        <div class="max-w-md">
          <h2 class="animate-rise text-[2.6rem] font-semibold leading-[1.08] tracking-tight" style="animation-delay: 120ms">
            <span class="text-gradient">Sua vida acadêmica,</span><br />
            <span class="text-white">com fluidez.</span>
          </h2>
          <p class="animate-rise mt-4 text-[15px] leading-relaxed text-dark-400" style="animation-delay: 200ms">
            Projetos, tarefas, calendário e inteligência artificial em um só lugar —
            desenhado para você focar no que importa.
          </p>

          <ul class="mt-9 space-y-4">
            <li
              v-for="(f, i) in features"
              :key="f.title"
              class="group flex items-start gap-4 animate-rise"
              :style="{ animationDelay: `${280 + i * 90}ms` }"
            >
              <span class="feature-icon">
                <span v-html="f.icon" class="flex h-5 w-5 items-center justify-center" />
              </span>
              <div>
                <p class="text-sm font-medium text-dark-100">{{ f.title }}</p>
                <p class="text-[13px] leading-snug text-dark-400">{{ f.desc }}</p>
              </div>
            </li>
          </ul>
        </div>

        <!-- Base: prova social em vidro -->
        <div class="animate-rise" style="animation-delay: 560ms">
          <div class="glass-soft inline-flex items-center gap-3 rounded-2xl px-4 py-3">
            <div class="flex -space-x-2">
              <span v-for="(c, i) in avatarColors" :key="i"
                    class="h-7 w-7 rounded-full border-2 border-dark-900"
                    :style="{ background: c }" />
            </div>
            <p class="text-[13px] text-dark-300">
              <span class="font-semibold text-white">+2.000</span> estudantes e pesquisadores
            </p>
          </div>
        </div>
      </aside>

      <!-- Área do formulário -->
      <main class="flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6">
        <!-- Logo compacto (mobile/tablet) -->
        <RouterLink to="/" class="mb-8 flex flex-col items-center gap-3 lg:hidden animate-scale-in">
          <BrandMark size="md" />
          <span class="text-lg font-semibold tracking-tight text-white">AcadFlow</span>
        </RouterLink>

        <div class="w-full max-w-md">
          <slot />
        </div>

        <div class="mt-8 w-full max-w-md">
          <slot name="footer" />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import BrandMark from './BrandMark.vue'
import AuthBackground from './AuthBackground.vue'

const features = [
  {
    title: 'Projetos e Kanban',
    desc: 'Organize trabalhos, TCCs e pesquisas em quadros visuais.',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>`,
  },
  {
    title: 'Calendário inteligente',
    desc: 'Prazos, reuniões e entregas sempre no seu radar.',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>`,
  },
  {
    title: 'Assistente com IA',
    desc: 'Resuma textos, gere ideias e acelere seus estudos.',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M12 3l1.9 4.6L18.5 9.5 14 11.4 12 16l-2-4.6L5.5 9.5 10 7.6 12 3z"/><path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z"/></svg>`,
  },
]

const avatarColors = ['#6366f1', '#8b5cf6', '#ec4899', '#06b6d4']
</script>

<style scoped>
/* ---------------- Feature icons ---------------- */
.feature-icon {
  display: inline-flex;
  height: 2.25rem;
  width: 2.25rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 0.75rem;
  color: rgb(var(--accent-300));
  background: rgb(var(--accent-500) / 0.12);
  border: 1px solid rgb(var(--accent-500) / 0.2);
  transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.3s, color 0.3s;
}
.group:hover .feature-icon {
  transform: translateY(-2px) scale(1.06);
  background: rgb(var(--accent-500) / 0.2);
  color: rgb(var(--accent-300));
}
</style>
