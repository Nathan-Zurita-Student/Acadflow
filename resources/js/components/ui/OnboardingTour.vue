<template>
  <Teleport to="body">
    <Transition name="ob-fade">
      <div v-if="tourOpen" class="fixed inset-0 z-[220] flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-md" @mousedown="skip" />

        <Transition name="ob-pop" appear>
          <div v-if="tourOpen" class="glass border-gradient relative w-full max-w-md overflow-hidden rounded-3xl shadow-card-float" role="dialog" aria-modal="true">
            <!-- Pular -->
            <button
              class="absolute right-4 top-4 z-10 rounded-lg px-2.5 py-1 text-xs font-medium text-dark-400 transition-colors hover:bg-dark-700/60 hover:text-dark-100"
              @click="skip"
            >
              Pular
            </button>

            <!-- Corpo -->
            <div class="px-8 pb-7 pt-12 text-center">
              <Transition name="ob-step" mode="out-in">
                <div :key="current" class="flex flex-col items-center">
                  <!-- Ícone / marca -->
                  <div class="mb-6">
                    <BrandMark v-if="step.brand" size="lg" />
                    <div v-else class="ob-tile">
                      <span class="ob-glow" aria-hidden="true" />
                      <AnimatedIcon :name="step.icon!" :size="40" active class="relative text-accent-300" />
                    </div>
                  </div>

                  <h2 class="text-xl font-semibold tracking-tight text-white">{{ step.title }}</h2>
                  <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-dark-400">{{ step.desc }}</p>
                </div>
              </Transition>
            </div>

            <!-- Progresso -->
            <div class="flex justify-center gap-1.5 pb-5">
              <button
                v-for="(s, i) in steps"
                :key="i"
                class="ob-dot"
                :class="{ 'ob-dot-active': i === current }"
                :aria-label="`Passo ${i + 1}`"
                @click="current = i"
              />
            </div>

            <!-- Controles -->
            <div class="flex items-center justify-between gap-3 border-t border-dark-700/70 px-6 py-4">
              <button v-if="current > 0" class="ob-ghost" @click="prev">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Anterior
              </button>
              <span v-else class="text-xs text-dark-600">{{ current + 1 }} de {{ steps.length }}</span>

              <button class="ob-primary" @click="next">
                {{ isLast ? 'Começar' : 'Próximo' }}
                <svg v-if="!isLast" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useOnboarding } from '@/composables/useOnboarding'
import BrandMark from '@/components/auth/BrandMark.vue'
import AnimatedIcon from '@/components/ui/AnimatedIcon.vue'

const auth = useAuthStore()
const { tourOpen, finishTour } = useOnboarding()

interface Step { brand?: boolean; icon?: string; title: string; desc: string }

const steps: Step[] = [
  { brand: true, title: 'Bem-vindo(a) ao AcadFlow', desc: 'Deixa a gente te mostrar o essencial em poucos segundos.' },
  { icon: 'home', title: 'Dashboard', desc: 'Seu panorama: projetos, tarefas e prazos reunidos num só lugar.' },
  { icon: 'folder', title: 'Projetos', desc: 'Organize trabalhos, TCCs e pesquisas em quadros Kanban visuais.' },
  { icon: 'calendar', title: 'Calendário', desc: 'Prazos, reuniões e entregas sempre no seu radar.' },
  { icon: 'task', title: 'Minhas Tarefas', desc: 'Tudo que é seu, em todos os projetos, numa lista só.' },
  { icon: 'sparkles', title: 'Assistente de IA', desc: 'Gere planos de trabalho e acelere seus estudos com inteligência artificial.' },
  { icon: 'settings', title: 'Configurações', desc: 'Personalize seu perfil, seu plano e o fluxo do seu Kanban.' },
]

const current = ref(0)
const step = computed(() => steps[current.value])
const isLast = computed(() => current.value === steps.length - 1)

watch(tourOpen, (open) => { if (open) current.value = 0 })

function next() {
  if (isLast.value) finishTour(auth.user?.id)
  else current.value++
}
function prev() {
  if (current.value > 0) current.value--
}
function skip() {
  finishTour(auth.user?.id)
}

function onKey(e: KeyboardEvent) {
  if (!tourOpen.value) return
  if (e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); next() }
  else if (e.key === 'ArrowLeft') { e.preventDefault(); prev() }
  else if (e.key === 'Escape') { e.preventDefault(); skip() }
}

onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))
</script>

<style scoped>
.ob-tile {
  position: relative;
  display: inline-flex;
  height: 5rem;
  width: 5rem;
  align-items: center;
  justify-content: center;
  border-radius: 1.5rem;
  color: rgb(var(--accent-300));
  background: linear-gradient(160deg, rgb(var(--accent-500) / 0.2), rgb(var(--accent-500) / 0.05));
  border: 1px solid rgb(var(--accent-500) / 0.25);
}
.ob-glow {
  position: absolute;
  inset: -0.6rem;
  border-radius: 9999px;
  background: radial-gradient(circle, rgb(var(--accent-500) / 0.4), transparent 70%);
  filter: blur(16px);
  animation: glowPulse 4s ease-in-out infinite;
}

.ob-dot {
  height: 6px;
  width: 6px;
  border-radius: 9999px;
  background: rgb(var(--d-600));
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.ob-dot-active {
  width: 22px;
  background: rgb(var(--accent-400));
}

.ob-ghost {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border-radius: 0.6rem;
  padding: 0.5rem 0.85rem;
  font-size: 0.85rem;
  font-weight: 500;
  color: rgb(var(--d-300));
  transition: background 0.2s, color 0.2s;
}
.ob-ghost:hover { background: rgb(var(--d-700) / 0.5); color: rgb(var(--d-100)); }

.ob-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  border-radius: 0.7rem;
  padding: 0.55rem 1.1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(180deg, rgb(var(--accent-500)), rgb(var(--accent-600)));
  box-shadow: 0 8px 22px -8px rgb(var(--accent-600) / 0.6), inset 0 1px 0 0 rgb(255 255 255 / 0.2);
  transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.2s;
}
.ob-primary:hover { filter: brightness(1.07); }
.ob-primary:active { transform: scale(0.97); }

/* Transições */
.ob-fade-enter-active, .ob-fade-leave-active { transition: opacity 0.25s ease; }
.ob-fade-enter-from, .ob-fade-leave-to { opacity: 0; }
.ob-pop-enter-active { transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease; }
.ob-pop-enter-from { opacity: 0; transform: translateY(12px) scale(0.96); }
.ob-step-enter-active, .ob-step-leave-active { transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
.ob-step-enter-from { opacity: 0; transform: translateX(16px); }
.ob-step-leave-to { opacity: 0; transform: translateX(-16px); }
</style>
