<template>
  <Teleport to="body">
    <Transition name="cp-fade">
      <div
        v-if="paletteOpen"
        class="fixed inset-0 z-[200] flex items-start justify-center px-4 pt-[14vh]"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @mousedown="closePalette" />

        <!-- Dialog -->
        <Transition name="cp-pop" appear>
          <div
            v-if="paletteOpen"
            class="glass border-gradient relative w-full max-w-xl overflow-hidden rounded-2xl shadow-card-float"
            role="dialog"
            aria-modal="true"
          >
            <!-- Campo de busca -->
            <div class="flex items-center gap-3 border-b border-dark-700/70 px-4">
              <svg class="h-5 w-5 flex-shrink-0 text-dark-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              <input
                ref="inputEl"
                v-model="query"
                type="text"
                placeholder="Buscar projetos, tarefas, membros…"
                class="w-full bg-transparent py-4 text-[15px] text-dark-100 placeholder-dark-500 outline-none"
                autocomplete="off"
                spellcheck="false"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="runActive"
              />
              <span v-if="loading" class="h-4 w-4 flex-shrink-0 rounded-full border-2 border-accent-500/30 border-t-accent-500 animate-spin" />
              <kbd v-else class="hidden flex-shrink-0 rounded border border-dark-600 bg-dark-800 px-1.5 py-0.5 text-[10px] font-medium text-dark-500 sm:block">ESC</kbd>
            </div>

            <!-- Resultados -->
            <div ref="listEl" class="max-h-[56vh] overflow-y-auto overscroll-contain p-2">
              <template v-if="flat.length">
                <div v-for="group in groups" :key="group.name" class="mb-1">
                  <p class="px-2.5 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-dark-500">{{ group.name }}</p>
                  <button
                    v-for="entry in group.items"
                    :key="entry.item.id"
                    type="button"
                    :data-idx="entry.index"
                    class="cp-item"
                    :class="{ 'cp-active': entry.index === activeIndex }"
                    @mousemove="activeIndex = entry.index"
                    @click="entry.item.run()"
                  >
                    <span class="cp-icon">
                      <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path :d="iconPath(entry.item.icon)" /></svg>
                    </span>
                    <span class="min-w-0 flex-1 text-left">
                      <span class="block truncate text-sm text-dark-100">{{ entry.item.label }}</span>
                      <span v-if="entry.item.sub" class="block truncate text-xs text-dark-500">{{ entry.item.sub }}</span>
                    </span>
                    <svg class="cp-enter h-4 w-4 flex-shrink-0 text-accent-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 10l-5 5 5 5"/><path d="M20 4v7a4 4 0 0 1-4 4H4"/></svg>
                  </button>
                </div>
              </template>

              <!-- Vazio -->
              <div v-else class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <svg class="h-8 w-8 text-dark-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <p class="mt-3 text-sm text-dark-400">Nenhum resultado para <span class="font-medium text-dark-200">"{{ query }}"</span></p>
                <p class="mt-1 text-xs text-dark-600">Tente outro termo.</p>
              </div>
            </div>

            <!-- Rodapé -->
            <div class="flex items-center gap-4 border-t border-dark-700/70 px-4 py-2.5 text-[11px] text-dark-500">
              <span class="flex items-center gap-1.5"><kbd class="cp-kbd">↑</kbd><kbd class="cp-kbd">↓</kbd> navegar</span>
              <span class="flex items-center gap-1.5"><kbd class="cp-kbd">↵</kbd> abrir</span>
              <span class="ml-auto flex items-center gap-1.5"><kbd class="cp-kbd">esc</kbd> fechar</span>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useShortcuts } from '@/composables/useShortcuts'
import { useOnboarding } from '@/composables/useOnboarding'
import { searchApi, type GlobalSearchResults } from '@/api/projects'

interface Cmd {
  id: string
  group: string
  label: string
  sub?: string
  icon: string
  run: () => void
}

const router = useRouter()
const { paletteOpen, closePalette, openHelp } = useShortcuts()
const { openTour } = useOnboarding()

const query = ref('')
const loading = ref(false)
const activeIndex = ref(0)
const inputEl = ref<HTMLInputElement | null>(null)
const listEl = ref<HTMLElement | null>(null)
const results = ref<GlobalSearchResults>({ projects: [], tasks: [], members: [] })

function go(path: string) {
  closePalette()
  router.push(path)
}

const staticCommands = computed<Cmd[]>(() => [
  { id: 'act-new-project', group: 'Ações', label: 'Novo projeto', sub: 'Criar um projeto', icon: 'plus', run: () => { closePalette(); router.push('/projects'); window.dispatchEvent(new CustomEvent('acadflow:new-project')) } },
  { id: 'act-help', group: 'Ações', label: 'Ver atalhos de teclado', icon: 'keyboard', run: () => { closePalette(); openHelp() } },
  { id: 'act-tour', group: 'Ações', label: 'Rever tour de boas-vindas', icon: 'sparkles', run: () => { closePalette(); openTour() } },
  { id: 'nav-dashboard', group: 'Navegação', label: 'Dashboard', icon: 'home', run: () => go('/') },
  { id: 'nav-projects', group: 'Navegação', label: 'Projetos', icon: 'folder', run: () => go('/projects') },
  { id: 'nav-mytasks', group: 'Navegação', label: 'Minhas Tarefas', icon: 'task', run: () => go('/my-tasks') },
  { id: 'nav-calendar', group: 'Navegação', label: 'Calendário', icon: 'calendar', run: () => go('/calendar') },
  { id: 'nav-settings', group: 'Navegação', label: 'Configurações', icon: 'settings', run: () => go('/settings') },
])

const q = computed(() => query.value.trim().toLowerCase())

const filteredStatic = computed(() => {
  if (!q.value) return staticCommands.value
  return staticCommands.value.filter(c => c.label.toLowerCase().includes(q.value) || c.sub?.toLowerCase().includes(q.value))
})

const dynamicItems = computed<Cmd[]>(() => {
  if (!q.value) return []
  const out: Cmd[] = []
  for (const p of results.value.projects) {
    out.push({ id: `p-${p.id}`, group: 'Projetos', label: p.name, sub: statusLabel(p.status), icon: 'folder', run: () => go(`/projects/${p.id}`) })
  }
  for (const t of results.value.tasks) {
    out.push({ id: `t-${t.id}`, group: 'Tarefas', label: t.title, sub: `em ${t.project.name}`, icon: 'task', run: () => go(`/projects/${t.project.id}/kanban`) })
  }
  for (const m of results.value.members) {
    out.push({ id: `m-${m.id}`, group: 'Membros', label: m.name, sub: m.email, icon: 'user', run: () => m.project_id ? go(`/projects/${m.project_id}/members`) : closePalette() })
  }
  return out
})

const flat = computed<Cmd[]>(() => {
  const order = ['Ações', 'Navegação', 'Projetos', 'Tarefas', 'Membros']
  const all = [...filteredStatic.value, ...dynamicItems.value]
  return order.flatMap(g => all.filter(c => c.group === g))
})

const groups = computed(() => {
  const order = ['Ações', 'Navegação', 'Projetos', 'Tarefas', 'Membros']
  const map: Record<string, { item: Cmd; index: number }[]> = {}
  flat.value.forEach((item, index) => { (map[item.group] ??= []).push({ item, index }) })
  return order.filter(g => map[g]?.length).map(g => ({ name: g, items: map[g] }))
})

function move(dir: number) {
  const n = flat.value.length
  if (!n) return
  activeIndex.value = (activeIndex.value + dir + n) % n
  scrollActiveIntoView()
}

function runActive() {
  flat.value[activeIndex.value]?.run()
}

function scrollActiveIntoView() {
  nextTick(() => {
    listEl.value?.querySelector<HTMLElement>(`[data-idx="${activeIndex.value}"]`)?.scrollIntoView({ block: 'nearest' })
  })
}

// Busca no servidor com debounce + cancelamento.
let debounceTimer: ReturnType<typeof setTimeout> | undefined
let controller: AbortController | null = null

watch(query, (val) => {
  activeIndex.value = 0
  if (debounceTimer) clearTimeout(debounceTimer)
  controller?.abort()

  const term = val.trim()
  if (!term) { results.value = { projects: [], tasks: [], members: [] }; loading.value = false; return }

  loading.value = true
  debounceTimer = setTimeout(async () => {
    controller = new AbortController()
    try {
      const { data } = await searchApi.global(term, controller.signal)
      results.value = data
    } catch (e: any) {
      if (e?.code !== 'ERR_CANCELED') results.value = { projects: [], tasks: [], members: [] }
    } finally {
      loading.value = false
    }
  }, 180)
})

// Ao abrir: limpa e foca.
watch(paletteOpen, (open) => {
  if (open) {
    query.value = ''
    results.value = { projects: [], tasks: [], members: [] }
    activeIndex.value = 0
    nextTick(() => inputEl.value?.focus())
  }
})

// Mantém o índice ativo dentro dos limites quando a lista muda.
watch(flat, (items) => {
  if (activeIndex.value >= items.length) activeIndex.value = Math.max(0, items.length - 1)
})

function statusLabel(s: string): string {
  return ({ active: 'Ativo', planning: 'Planejamento', completed: 'Concluído', on_hold: 'Em pausa', archived: 'Arquivado' } as Record<string, string>)[s] ?? s
}

function iconPath(name: string): string {
  const icons: Record<string, string> = {
    home: 'M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5z',
    folder: 'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z',
    task: 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z',
    settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z',
    plus: 'M12 5v14M5 12h14',
    keyboard: 'M6 8h.01M10 8h.01M14 8h.01M18 8h.01M6 12h.01M10 12h.01M14 12h.01M18 12h.01M7 16h10M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
    user: 'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z',
    sparkles: 'M12 3l1.9 4.6L18.5 9.5 14 11.4 12 16l-2-4.6L5.5 9.5 10 7.6 12 3z',
  }
  return icons[name] ?? icons.task
}
</script>

<style scoped>
.cp-item {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 0.75rem;
  border-radius: 0.6rem;
  padding: 0.55rem 0.65rem;
  transition: background 0.12s ease;
}
.cp-icon {
  display: inline-flex;
  height: 2.25rem;
  width: 2.25rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 0.6rem;
  color: rgb(var(--d-300));
  background: rgb(var(--d-800) / 0.8);
  border: 1px solid rgb(255 255 255 / 0.06);
  transition: color 0.15s, background 0.15s, border-color 0.15s;
}
.cp-enter { opacity: 0; transform: translateX(-4px); transition: opacity 0.15s, transform 0.15s; }
.cp-active { background: rgb(var(--accent-500) / 0.12); }
.cp-active .cp-icon {
  color: rgb(var(--accent-300));
  background: rgb(var(--accent-500) / 0.18);
  border-color: rgb(var(--accent-500) / 0.3);
}
.cp-active .cp-enter { opacity: 1; transform: translateX(0); }

.cp-kbd {
  display: inline-flex;
  min-width: 1.1rem;
  align-items: center;
  justify-content: center;
  border-radius: 0.25rem;
  border: 1px solid rgb(var(--d-600));
  background: rgb(var(--d-800));
  padding: 0.05rem 0.3rem;
  font-size: 10px;
  line-height: 1.2;
  color: rgb(var(--d-400));
}

.cp-fade-enter-active, .cp-fade-leave-active { transition: opacity 0.2s ease; }
.cp-fade-enter-from, .cp-fade-leave-to { opacity: 0; }
.cp-pop-enter-active { transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.28s ease; }
.cp-pop-enter-from { opacity: 0; transform: translateY(-8px) scale(0.97); }
</style>
