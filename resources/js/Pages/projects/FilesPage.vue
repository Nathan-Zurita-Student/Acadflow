<template>
  <div class="space-y-6 stagger-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Arquivos</h1>
        <p class="text-dark-400 text-sm">{{ filtered.length }} de {{ attachments.length }} arquivo{{ attachments.length !== 1 ? 's' : '' }}</p>
      </div>
      <button @click="showUploadModal = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        Upload
      </button>
    </div>

    <!-- Search + type filters -->
    <div class="flex flex-wrap items-center gap-2">
      <div class="relative flex-1 min-w-[160px] max-w-xs">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-dark-500 pointer-events-none"
          fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input v-model="searchQuery" placeholder="Buscar arquivos..."
          class="w-full text-xs bg-dark-800 border border-dark-700 rounded-lg pl-7 pr-3 py-1.5 text-dark-200 placeholder-dark-600 focus:outline-none focus:border-accent-500" />
      </div>
      <div class="flex items-center gap-1">
        <button
          v-for="ft in fileTypeFilters" :key="ft.value"
          @click="filterType = filterType === ft.value ? '' : ft.value"
          :class="['text-xs px-2.5 py-1.5 rounded-lg border transition-colors',
            filterType === ft.value
              ? 'bg-accent-500/15 border-accent-500/30 text-accent-400'
              : 'border-dark-700 text-dark-400 hover:text-dark-200 hover:border-dark-600']">
          {{ ft.label }}
        </button>
      </div>
      <button v-if="searchQuery || filterType"
        @click="searchQuery = ''; filterType = ''"
        class="text-xs text-dark-500 hover:text-dark-300 px-2 py-1.5 rounded-lg hover:bg-dark-700 transition-colors">
        Limpar
      </button>
    </div>

    <!-- Upload progress -->
    <div v-if="uploading" class="card">
      <div class="flex items-center gap-3">
        <div class="w-4 h-4 border-2 border-accent-500/30 border-t-accent-500 rounded-full animate-spin flex-shrink-0" />
        <span class="text-sm text-dark-300">Enviando arquivo...</span>
      </div>
    </div>

    <!-- Files grid -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 stagger-in">
      <Skeleton v-for="i in 6" :key="i" h="14rem" rounded="rounded-xl" />
    </div>

    <div v-else-if="filtered.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 stagger-in">
      <div v-for="a in filtered" :key="a.id"
        class="card card-glow !p-0 overflow-hidden group flex flex-col">

        <!-- Preview -->
        <div class="relative h-40 bg-dark-900/60 flex items-center justify-center overflow-hidden">
          <img v-if="isImage(a)" :src="a.url" :alt="a.name" loading="lazy"
            class="w-full h-full object-cover" />
          <video v-else-if="isVideo(a)" :src="a.url" controls preload="metadata"
            class="w-full h-full object-contain bg-black" />
          <iframe v-else-if="isPdf(a)" :src="`${a.url}#toolbar=0&navpanes=0&view=FitH`"
            class="w-full h-full bg-white" :title="`Pré-visualização de ${a.name}`" loading="lazy" />
          <div v-else class="w-14 h-14 rounded-xl flex items-center justify-center" :class="fileIconBg(a.mime_type)">
            <span class="text-sm font-bold" :class="fileIconColor(a.mime_type)">{{ fileExt(a.name) }}</span>
          </div>

          <!-- Hover actions overlay -->
          <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <a :href="a.url" target="_blank" rel="noopener"
              class="p-1.5 rounded-lg bg-dark-900/80 backdrop-blur hover:bg-dark-700 text-dark-200 transition-colors"
              title="Abrir em nova aba" @click.stop>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
            </a>
            <a :href="a.url" :download="a.name"
              class="p-1.5 rounded-lg bg-dark-900/80 backdrop-blur hover:bg-dark-700 text-dark-200 transition-colors"
              title="Baixar arquivo" @click.stop>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </a>
            <button @click.stop="deleteFile(a.id)"
              class="p-1.5 rounded-lg bg-dark-900/80 backdrop-blur hover:bg-red-600/30 text-dark-200 hover:text-red-400 transition-colors"
              title="Excluir arquivo">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Info -->
        <div class="p-3 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" :class="fileIconBg(a.mime_type)">
            <span class="text-[10px] font-bold" :class="fileIconColor(a.mime_type)">{{ fileExt(a.name) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-dark-100 truncate" :title="a.name">{{ a.name }}</p>
            <p class="text-xs text-dark-500 mt-0.5 truncate">{{ formatSize(a.size) }} • {{ a.uploader?.name }}</p>
          </div>
        </div>
      </div>
    </div>

    <EmptyState
      v-else-if="searchQuery || filterType"
      title="Nenhum arquivo encontrado"
      description="Tente ajustar a busca ou o filtro de tipo."
    >
      <template #icon>
        <svg class="relative h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      </template>
      <template #action>
        <button class="btn-secondary" @click="searchQuery = ''; filterType = ''">Limpar filtros</button>
      </template>
    </EmptyState>

    <EmptyState
      v-else
      title="Nenhum arquivo ainda"
      description="Faça upload de documentos, imagens, vídeos e muito mais."
    >
      <template #action>
        <button class="btn-primary" @click="showUploadModal = true">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          Enviar arquivo
        </button>
      </template>
    </EmptyState>
  </div>

  <!-- Upload Modal -->
  <Teleport to="body">
    <div v-if="showUploadModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
      @click.self="closeUploadModal">
      <div class="w-full max-w-md bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-dark-700">
          <h2 class="font-semibold text-white">Enviar arquivo</h2>
          <button @click="closeUploadModal" class="p-1 rounded-lg hover:bg-dark-700 text-dark-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-4">
          <!-- File picker -->
          <div
            class="border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer"
            :class="isDragging ? 'border-accent-500 bg-accent-500/5' : 'border-dark-600 hover:border-dark-500'"
            @dragover.prevent="isDragging = true"
            @dragleave.self="isDragging = false"
            @drop.prevent="onDrop"
            @click="fileInput?.click()"
          >
            <input ref="fileInput" type="file" class="hidden" @change="onFileSelect" />
            <div v-if="!selectedFile">
              <svg class="w-8 h-8 text-dark-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <p class="text-sm text-dark-400">Arraste um arquivo ou <span class="text-accent-400 font-medium">clique para selecionar</span></p>
              <p class="text-xs text-dark-600 mt-1">Até 50MB</p>
            </div>
            <div v-else class="flex items-center gap-3 text-left">
              <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                :class="fileIconBg(selectedFile.type)">
                <span class="text-xs font-bold" :class="fileIconColor(selectedFile.type)">
                  {{ fileExt(selectedFile.name) }}
                </span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-dark-200 truncate">{{ selectedFile.name }}</p>
                <p class="text-xs text-dark-500">{{ formatSize(selectedFile.size) }}</p>
              </div>
              <button @click.stop="selectedFile = null" class="text-dark-500 hover:text-dark-300">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Nome customizado -->
          <div>
            <label class="label">Nome do arquivo <span class="text-dark-600 font-normal">(opcional)</span></label>
            <input
              v-model="uploadName"
              class="input"
              placeholder="Ex: Relatório Final, Apresentação..."
            />
            <p class="text-xs text-dark-600 mt-1">Se não preenchido, usa o nome original do arquivo.</p>
          </div>

          <div class="flex gap-3 pt-1">
            <button @click="closeUploadModal" class="btn-secondary flex-1 justify-center">Cancelar</button>
            <button
              @click="handleUpload"
              :disabled="!selectedFile || uploading"
              class="btn-primary flex-1 justify-center disabled:opacity-50">
              <span v-if="uploading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              <span v-else>Enviar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { attachmentsApi } from '@/api/projects'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import Skeleton from '@/components/ui/Skeleton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import type { Attachment } from '@/types'

const route = useRoute()
const projectId = Number(route.params.id)
const toast = useToast()
const { confirm } = useConfirm()
const loading = ref(true)
const uploading = ref(false)
const attachments = ref<Attachment[]>([])
const searchQuery = ref('')
const filterType = ref('')

const fileTypeFilters = [
  { value: 'image', label: 'Imagens' },
  { value: 'pdf',   label: 'PDFs' },
  { value: 'doc',   label: 'Documentos' },
  { value: 'video', label: 'Vídeos' },
]

const filtered = computed(() => {
  let list = attachments.value
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(a => a.name.toLowerCase().includes(q) || a.uploader?.name?.toLowerCase().includes(q))
  }
  if (filterType.value === 'image') list = list.filter(a => a.mime_type.startsWith('image/'))
  else if (filterType.value === 'pdf') list = list.filter(a => a.mime_type === 'application/pdf')
  else if (filterType.value === 'doc') list = list.filter(a => a.mime_type.includes('word') || a.mime_type.includes('document') || a.mime_type.includes('sheet') || a.mime_type.includes('excel') || a.mime_type.includes('presentation'))
  else if (filterType.value === 'video') list = list.filter(a => a.mime_type.startsWith('video/'))
  return list
})

// upload modal
const showUploadModal = ref(false)
const selectedFile = ref<File | null>(null)
const uploadName = ref('')
const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

onMounted(async () => {
  try {
    const res = await attachmentsApi.list(projectId)
    attachments.value = res.data
  } finally { loading.value = false }
})

function closeUploadModal() {
  showUploadModal.value = false
  selectedFile.value = null
  uploadName.value = ''
  isDragging.value = false
}

function onFileSelect(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (f) selectedFile.value = f
}

function onDrop(e: DragEvent) {
  isDragging.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) selectedFile.value = f
}

async function handleUpload() {
  if (!selectedFile.value) return
  uploading.value = true
  try {
    const res = await attachmentsApi.upload(projectId, selectedFile.value, undefined, uploadName.value.trim() || undefined)
    attachments.value.unshift(res.data)
    closeUploadModal()
    toast.success('Arquivo enviado com sucesso.')
  } catch {
    toast.error('Erro ao enviar arquivo.')
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

async function deleteFile(id: number) {
  if (!await confirm({ message: 'Excluir este arquivo?', variant: 'danger' })) return
  await attachmentsApi.delete(projectId, id)
  attachments.value = attachments.value.filter(a => a.id !== id)
  toast.success('Arquivo excluído.')
}

function isImage(a: Attachment) { return a.mime_type.startsWith('image/') }
function isVideo(a: Attachment) { return a.mime_type.startsWith('video/') }
function isPdf(a: Attachment)   { return a.mime_type === 'application/pdf' }

function fileExt(name: string) {
  return name.split('.').pop()?.toUpperCase().slice(0, 4) ?? 'FILE'
}

function fileIconBg(mime: string) {
  if (mime.includes('pdf')) return 'bg-red-600/20'
  if (mime.includes('image')) return 'bg-blue-600/20'
  if (mime.includes('word') || mime.includes('document')) return 'bg-accent-600/20'
  if (mime.includes('sheet') || mime.includes('excel')) return 'bg-emerald-600/20'
  return 'bg-dark-700'
}

function fileIconColor(mime: string) {
  if (mime.includes('pdf')) return 'text-red-400'
  if (mime.includes('image')) return 'text-blue-400'
  if (mime.includes('word') || mime.includes('document')) return 'text-accent-400'
  if (mime.includes('sheet') || mime.includes('excel')) return 'text-emerald-400'
  return 'text-dark-400'
}

function formatSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1048576).toFixed(1)} MB`
}
</script>
