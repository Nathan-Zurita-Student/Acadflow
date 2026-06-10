<template>
  <div class="flex h-[calc(100vh-112px)] animate-fade-in overflow-hidden">
    <!-- Sidebar: notes list (hidden on mobile when a note is selected) -->
    <aside
      :class="[
        'flex-shrink-0 border-r border-dark-700 flex flex-col transition-all duration-200',
        'md:w-64 md:flex',
        selected ? 'hidden md:flex' : 'flex w-full md:w-64',
      ]"
    >
      <div class="p-3 border-b border-dark-700">
        <button @click="createNote" class="btn-primary w-full justify-center text-sm">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nova Nota
        </button>
      </div>

      <div class="flex-1 overflow-y-auto">
        <div v-if="loading" class="flex justify-center py-8">
          <div class="w-5 h-5 border-2 border-accent-500/30 border-t-accent-500 rounded-full animate-spin" />
        </div>

        <div
          v-for="note in notes" :key="note.id"
          @click="select(note)"
          class="px-3 py-3 cursor-pointer border-b border-dark-700/50 hover:bg-dark-700/40 transition-colors"
          :class="selected?.id === note.id ? 'bg-accent-600/10 border-l-2 border-l-accent-500' : ''"
        >
          <p class="text-sm font-medium text-dark-200 truncate">{{ note.title }}</p>
          <p class="text-xs text-dark-600 mt-0.5 line-clamp-2 leading-relaxed">
            {{ note.content || 'Sem conteúdo' }}
          </p>
          <p class="text-[10px] text-dark-700 mt-1">{{ timeAgo(note.updated_at) }}</p>
        </div>

        <div v-if="!loading && !notes.length" class="flex flex-col items-center gap-2 py-10 text-dark-600 px-4 text-center">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-xs">Nenhuma nota ainda</p>
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
        <!-- Toolbar -->
        <div class="flex items-center gap-2 px-4 py-3 border-b border-dark-700 flex-shrink-0">
          <!-- Mobile: back to list -->
          <button @click="selected = null" class="md:hidden p-1.5 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-dark-200 transition-colors flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <input
            v-model="selected.title"
            class="flex-1 bg-transparent text-lg font-bold text-dark-100 border-none outline-none placeholder-dark-600"
            placeholder="Título da nota"
            @change="saveNote"
          />
          <div class="flex items-center gap-2 flex-shrink-0">
            <span class="text-[10px] text-dark-600 hidden sm:block">{{ saveStatus }}</span>
            <button @click="deleteNote(selected)" class="p-1.5 rounded-lg hover:bg-red-600/20 text-dark-500 hover:text-red-400 transition-colors">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Content area -->
        <textarea
          v-model="selected.content"
          class="flex-1 resize-none bg-transparent text-dark-300 text-sm leading-relaxed p-5 outline-none placeholder-dark-700 font-mono"
          placeholder="Comece a escrever... (suporta Markdown)"
          @input="debounceSave"
        />

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
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import { useToast } from '@/composables/useToast'
import { notesApi } from '@/api/projects'
import type { ProjectNote } from '@/types'
import { useTimeAgo } from '@/composables/useTimeAgo'

const route = useRoute()
const projectId = Number(route.params.id)

const projectsStore = useProjectsStore()
const toast = useToast()

const notes      = ref<ProjectNote[]>([])
const selected   = ref<ProjectNote | null>(null)
const loading    = ref(true)
const saveStatus = ref('')
let saveTimer: ReturnType<typeof setTimeout> | null = null

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
  if (!confirm(`Remover "${note.title}"?`)) return
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
