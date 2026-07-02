<template>
  <div class="flex h-full min-h-[70vh] rounded-xl border border-dark-700 bg-dark-800 overflow-hidden animate-fade-in">
    <!-- Sidebar: notes list (hidden on mobile when a note is selected) -->
    <aside
      :class="[
        'flex-shrink-0 border-r border-dark-700 flex flex-col transition-all duration-200',
        'md:w-64 md:flex',
        selected ? 'hidden md:flex' : 'flex w-full md:w-64',
      ]"
    >
      <div class="p-3 border-b border-dark-700 space-y-2">
        <button @click="createNote" class="btn-primary w-full justify-center text-sm">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nova Nota
        </button>
        <!-- Search -->
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-dark-500 pointer-events-none"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input v-model="searchQuery" placeholder="Buscar nas notas..."
            class="w-full text-sm bg-dark-900 border border-dark-700 rounded-lg pl-9 pr-3 py-2 text-dark-200 placeholder-dark-600 focus:outline-none focus:border-accent-500" />
        </div>
      </div>

      <div class="flex-1 overflow-y-auto">
        <div v-if="loading" class="flex justify-center py-8">
          <div class="w-5 h-5 border-2 border-accent-500/30 border-t-accent-500 rounded-full animate-spin" />
        </div>

        <div
          v-for="note in filteredNotes" :key="note.id"
          @click="select(note)"
          class="px-3 py-3 cursor-pointer border-b border-dark-700/50 hover:bg-dark-700/40 transition-colors"
          :class="selected?.id === note.id ? 'bg-accent-600/10 border-l-2 border-l-accent-500' : ''"
        >
          <p class="text-sm font-medium text-dark-200 truncate">{{ note.title }}</p>
          <p class="text-xs text-dark-600 mt-0.5 line-clamp-2 leading-relaxed">
            {{ stripMd(note.content) || 'Sem conteúdo' }}
          </p>
          <p class="text-[10px] text-dark-700 mt-1">{{ timeAgo(note.updated_at) }}</p>
        </div>

        <div v-if="!loading && !filteredNotes.length" class="flex flex-col items-center gap-2 py-10 text-dark-600 px-4 text-center">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-xs">{{ searchQuery ? 'Nenhuma nota encontrada' : 'Nenhuma nota ainda' }}</p>
        </div>
      </div>
    </aside>

    <!-- Main: editor (hidden on mobile when no note is selected) -->
    <main
      :class="[
        'flex-1 flex flex-col min-w-0',
        !selected ? 'hidden md:flex' : 'flex',
      ]"
    >
      <template v-if="selected">
        <!-- Top bar -->
        <div class="flex items-center gap-2 px-3 sm:px-4 py-2.5 sm:py-3 border-b border-dark-700 flex-shrink-0">
          <button @click="selected = null" class="md:hidden p-1.5 -ml-1 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-dark-200 transition-colors flex-shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <input
            v-model="selected.title"
            class="flex-1 min-w-0 bg-transparent text-base sm:text-lg font-bold text-dark-100 border-none outline-none placeholder-dark-600"
            placeholder="Título da nota"
            @change="saveNote"
          />
          <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
            <span class="text-[10px] text-dark-600 hidden sm:block whitespace-nowrap">{{ saveStatus }}</span>
            <!-- View mode toggle (Dividir só faz sentido em telas largas) -->
            <div class="flex items-center gap-0.5 bg-dark-900 border border-dark-700 rounded-lg p-0.5">
              <button v-for="m in viewModes" :key="m.value"
                @click="viewMode = m.value"
                :title="m.label"
                :class="['px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors',
                  m.value === 'split' ? 'hidden md:block' : '',
                  viewMode === m.value ? 'bg-dark-600 text-white' : 'text-dark-400 hover:text-dark-200']">
                {{ m.label }}
              </button>
            </div>
            <button @click="deleteNote(selected)" class="p-1.5 rounded-lg hover:bg-red-600/20 text-dark-500 hover:text-red-400 transition-colors flex-shrink-0">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Formatting toolbar (hidden in pure preview) -->
        <div v-if="viewMode !== 'preview'" class="flex items-center gap-0.5 px-2 sm:px-3 py-1.5 border-b border-dark-700 flex-shrink-0 overflow-x-auto">
          <button v-for="t in toolbar" :key="t.label" @click="t.action" :title="t.label"
            class="p-1.5 rounded-md text-dark-400 hover:text-dark-100 hover:bg-dark-700 transition-colors flex-shrink-0">
            <Icon :name="t.icon" :size="18" />
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 flex min-h-0">
          <textarea
            v-if="viewMode !== 'preview'"
            ref="editorRef"
            v-model="selected.content"
            class="flex-1 resize-none bg-transparent text-dark-300 text-sm leading-relaxed p-4 sm:p-5 outline-none placeholder-dark-700 font-mono min-w-0"
            placeholder="Comece a escrever... (suporta Markdown: # título, **negrito**, - lista, - [ ] checklist, > citação, `código`, | tabela |)"
            @input="debounceSave"
            @keydown="onEditorKeydown"
          />
          <div
            v-if="viewMode !== 'edit'"
            class="md-preview overflow-y-auto p-4 sm:p-5 text-sm text-dark-200 min-w-0"
            :class="viewMode === 'split' ? 'hidden md:block md:flex-1 border-l border-dark-700' : 'flex-1'"
            v-html="renderedContent"
          />
        </div>

        <!-- Footer meta -->
        <div class="px-5 py-2 border-t border-dark-700 flex items-center gap-3 text-[10px] text-dark-700 flex-shrink-0">
          <span>Por {{ selected.author?.name }}</span>
          <span>·</span>
          <span>Atualizado {{ timeAgo(selected.updated_at) }}</span>
        </div>
      </template>

      <div v-else class="flex-1 flex flex-col items-center justify-center gap-3 text-dark-600">
        <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-sm">Selecione uma nota ou crie uma nova</p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useMarkdown } from '@/composables/useMarkdown'
import { notesApi } from '@/api/projects'
import Icon from '@/components/ui/Icon.vue'
import type { ProjectNote } from '@/types'
import { useTimeAgo } from '@/composables/useTimeAgo'

const route = useRoute()
const projectId = Number(route.params.id)

const projectsStore = useProjectsStore()
const toast = useToast()
const { render } = useMarkdown()

const notes      = ref<ProjectNote[]>([])
const selected   = ref<ProjectNote | null>(null)
const loading    = ref(true)
const saveStatus = ref('')
const searchQuery = ref('')
const viewMode   = ref<'edit' | 'split' | 'preview'>(
  typeof window !== 'undefined' && window.innerWidth < 768 ? 'edit' : 'split'
)
const editorRef  = ref<HTMLTextAreaElement | null>(null)
let saveTimer: ReturnType<typeof setTimeout> | null = null

const viewModes = [
  { value: 'edit' as const,    label: 'Editar' },
  { value: 'split' as const,   label: 'Dividir' },
  { value: 'preview' as const, label: 'Visualizar' },
]

const renderedContent = computed(() => render(selected.value?.content) || '<p class="text-dark-600 italic">Nada para visualizar ainda.</p>')

const filteredNotes = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return notes.value
  return notes.value.filter(n =>
    n.title.toLowerCase().includes(q) || (n.content ?? '').toLowerCase().includes(q)
  )
})

function stripMd(src: string | null | undefined): string {
  return (src ?? '').replace(/[#*`>\-\[\]()_~|]/g, '').replace(/\s+/g, ' ').trim()
}

// ── Markdown toolbar ──
const toolbar = [
  { label: 'Negrito (Ctrl+B)', icon: 'format_bold',          action: () => surround('**', '**', 'negrito') },
  { label: 'Itálico (Ctrl+I)', icon: 'format_italic',        action: () => surround('*', '*', 'itálico') },
  { label: 'Título',           icon: 'title',                action: () => prefixLines('## ') },
  { label: 'Lista',            icon: 'format_list_bulleted', action: () => prefixLines('- ') },
  { label: 'Checklist',        icon: 'checklist',            action: () => prefixLines('- [ ] ') },
  { label: 'Citação',          icon: 'format_quote',         action: () => prefixLines('> ') },
  { label: 'Código',           icon: 'code',                 action: () => surround('`', '`', 'código') },
  { label: 'Link',             icon: 'link',                 action: () => surround('[', '](https://)', 'texto') },
  { label: 'Tabela',           icon: 'table_chart',          action: insertTable },
]

function surround(before: string, after = before, placeholder = '') {
  const ta = editorRef.value
  if (!ta || !selected.value) return
  const start = ta.selectionStart, end = ta.selectionEnd
  const val = selected.value.content ?? ''
  const sel = val.slice(start, end) || placeholder
  selected.value.content = val.slice(0, start) + before + sel + after + val.slice(end)
  nextTick(() => {
    ta.focus()
    const pos = start + before.length
    ta.setSelectionRange(pos, pos + sel.length)
  })
  debounceSave()
}

function prefixLines(prefix: string) {
  const ta = editorRef.value
  if (!ta || !selected.value) return
  const start = ta.selectionStart, end = ta.selectionEnd
  const val = selected.value.content ?? ''
  const lineStart = val.lastIndexOf('\n', start - 1) + 1
  const sel = val.slice(lineStart, end)
  const replaced = sel.split('\n').map(l => prefix + l).join('\n')
  selected.value.content = val.slice(0, lineStart) + replaced + val.slice(end)
  nextTick(() => ta.focus())
  debounceSave()
}

function insertTable() {
  const ta = editorRef.value
  if (!ta || !selected.value) return
  const tpl = '\n| Coluna 1 | Coluna 2 |\n| --- | --- |\n| valor | valor |\n'
  const start = ta.selectionStart
  const val = selected.value.content ?? ''
  selected.value.content = val.slice(0, start) + tpl + val.slice(start)
  nextTick(() => { ta.focus(); const p = start + tpl.length; ta.setSelectionRange(p, p) })
  debounceSave()
}

function onEditorKeydown(e: KeyboardEvent) {
  if (e.ctrlKey || e.metaKey) {
    if (e.key === 'b') { e.preventDefault(); surround('**', '**', 'negrito') }
    else if (e.key === 'i') { e.preventDefault(); surround('*', '*', 'itálico') }
  }
}

function select(note: ProjectNote) {
  selected.value = { ...note }
}

async function createNote() {
  try {
    const { data } = await notesApi.create(projectId, { title: 'Nova nota', content: '' })
    notes.value.unshift(data)
    select(data)
  } catch {
    toast.error('Erro ao criar nota.')
  }
}

function debounceSave() {
  saveStatus.value = 'Digitando...'
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(saveNote, 1200)
}

async function saveNote() {
  if (!selected.value) return
  saveStatus.value = 'Salvando...'
  try {
    const { data } = await notesApi.update(projectId, selected.value.id, {
      title: selected.value.title,
      content: selected.value.content ?? '',
    })
    const idx = notes.value.findIndex(n => n.id === data.id)
    if (idx !== -1) notes.value[idx] = data
    selected.value.updated_at = data.updated_at
    saveStatus.value = 'Salvo'
    setTimeout(() => { saveStatus.value = '' }, 2000)
  } catch {
    saveStatus.value = 'Erro ao salvar'
  }
}

async function deleteNote(note: ProjectNote) {
  const { confirm } = useConfirm()
  if (!await confirm({ message: `Remover "${note.title}"?`, confirmText: 'Remover', variant: 'danger' })) return
  try {
    await notesApi.delete(projectId, note.id)
    notes.value = notes.value.filter(n => n.id !== note.id)
    if (selected.value?.id === note.id) selected.value = notes.value[0] ?? null
    toast.success('Nota removida.')
  } catch {
    toast.error('Erro ao remover nota.')
  }
}

const { timeAgo } = useTimeAgo()

onMounted(async () => {
  await projectsStore.fetchProject(projectId)
  try {
    const { data } = await notesApi.list(projectId)
    notes.value = data
    if (data.length) select(data[0])
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.md-preview :deep(h1) { font-size: 1.5rem; font-weight: 700; color: #f1f5f9; margin: 0.6em 0 0.4em; }
.md-preview :deep(h2) { font-size: 1.25rem; font-weight: 700; color: #e2e8f0; margin: 0.6em 0 0.4em; }
.md-preview :deep(h3) { font-size: 1.1rem; font-weight: 600; color: #e2e8f0; margin: 0.5em 0 0.3em; }
.md-preview :deep(p)  { margin: 0.5em 0; line-height: 1.65; }
.md-preview :deep(ul) { list-style: disc; padding-left: 1.4em; margin: 0.5em 0; }
.md-preview :deep(ol) { list-style: decimal; padding-left: 1.4em; margin: 0.5em 0; }
.md-preview :deep(li) { margin: 0.2em 0; }
.md-preview :deep(li input[type="checkbox"]) { margin-right: 0.4em; }
.md-preview :deep(a)  { color: #818cf8; text-decoration: underline; }
.md-preview :deep(blockquote) { border-left: 3px solid #475569; padding-left: 1em; color: #94a3b8; margin: 0.6em 0; font-style: italic; }
.md-preview :deep(code) { background: rgba(0,0,0,0.3); padding: 0.1em 0.35em; border-radius: 4px; font-size: 0.85em; font-family: ui-monospace, monospace; }
.md-preview :deep(pre) { background: rgba(0,0,0,0.35); padding: 0.9em; border-radius: 8px; overflow-x: auto; margin: 0.6em 0; }
.md-preview :deep(pre code) { background: none; padding: 0; }
.md-preview :deep(table) { border-collapse: collapse; margin: 0.6em 0; width: 100%; }
.md-preview :deep(th), .md-preview :deep(td) { border: 1px solid #334155; padding: 0.4em 0.7em; text-align: left; }
.md-preview :deep(th) { background: rgba(255,255,255,0.04); font-weight: 600; }
.md-preview :deep(img) { max-width: 100%; border-radius: 8px; margin: 0.5em 0; }
.md-preview :deep(hr) { border: none; border-top: 1px solid #334155; margin: 1em 0; }
</style>
