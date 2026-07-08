<template>
  <div class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-dark-950 px-4 py-6 text-dark-100 sm:px-6">
    <!-- ============================ BACKGROUND VIVO ============================ -->
    <AuthBackground />

    <!-- ============ RETÂNGULO "LIQUID GLASS" (marca + formulário) ============ -->
    <div class="auth-card relative z-10 grid w-full max-w-md overflow-hidden rounded-[26px] lg:max-w-4xl lg:grid-cols-[1.02fr_1fr]">
      <!-- Painel de marca — somente desktop -->
      <aside class="brand-side relative hidden flex-col justify-center gap-12 overflow-hidden p-9 lg:flex xl:p-10">
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

      </aside>

      <!-- Área do formulário -->
      <main class="flex flex-col justify-center p-6 sm:p-8 lg:p-9">
        <!-- Logo compacto (mobile/tablet) -->
        <RouterLink v-if="!hideMobileLogo" to="/" class="mb-6 flex flex-col items-center gap-3 lg:hidden animate-scale-in">
          <BrandMark size="md" />
          <span class="text-lg font-semibold tracking-tight text-white">AcadFlow</span>
        </RouterLink>

        <div class="w-full">
          <slot />
        </div>

        <div class="mt-5 w-full">
          <slot name="footer" />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import BrandMark from './BrandMark.vue'
import AuthBackground from './AuthBackground.vue'

defineProps<{ hideMobileLogo?: boolean }>()

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
</script>

<style scoped>
/* ---------------- Liquid glass card ---------------- */
.auth-card {
  background: linear-gradient(155deg, rgb(var(--d-800) / 0.55), rgb(var(--d-900) / 0.42));
  backdrop-filter: blur(25px) saturate(160%);
  -webkit-backdrop-filter: blur(30px) saturate(160%);
  border: 1px solid rgb(255 255 255 / 0.10);
  box-shadow:
    0 40px 90px -30px rgb(0 0 0 / 0.75),
    0 8px 30px -12px rgb(0 0 0 / 0.5),
    inset 0 1px 0 0 rgb(255 255 255 / 0.10);
}

/* Lado da marca: leve tint em accent + divisória fina separando do formulário. */
.brand-side {
  background: linear-gradient(160deg, rgb(var(--accent-500) / 0.10), transparent 62%);
}
.brand-side::after {
  content: '';
  position: absolute;
  top: 8%;
  bottom: 8%;
  right: 0;
  width: 1px;
  background: linear-gradient(to bottom, transparent, rgb(255 255 255 / 0.10), transparent);
}

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
