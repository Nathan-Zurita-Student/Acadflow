<template>
  <div class="space-y-6 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Arquivos</h1>
        <p class="text-dark-400 text-sm">{{ attachments.length }} arquivo{{ attachments.length !== 1 ? 's' : '' }}</p>
      </div>
      <button @click="showUploadModal = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        Upload
      </button>
    </div>

    <!-- Upload progress -->
    <div v-if="uploading" class="card">
      <div class="flex items-center gap-3">
        <div class="w-4 h-4 border-2 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin flex-shrink-0" />
        <span class="text-sm text-dark-300">Enviando arquivo...</span>
      </div>
    </div>

    <!-- Files grid -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="card animate-pulse h-20" />
    </div>

    <div v-else-if="attachments.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="a in attachments" :key="a.id"
        class="card hover:border-dark-600 transition-colors flex items-center gap-4 group">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
          :class="fileIconBg(a.mime_type)">
          <span class="text-xs font-bold" :class="fileIconColor(a.mime_type)">
            {{ fileExt(a.name) }}
          </span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-dark-100 truncate">{{ a.name }}</p>
          <p class="text-xs text-dark-500 mt-0.5">
            {{ formatSize(a.size) }} • {{ a.uploader?.name }}
          </p>
        </div>
        <div class="flex items-center gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
          <!-- Abrir no navegador -->
          <a :href="a.url" target="_blank" rel="noopener"
            class="p-1.5 rounded hover:bg-dark-700 text-dark-400 hover:text-dark-200 transition-colors"
            title="Abrir no navegador">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
          </a>
          <!-- Download -->
          <a :href="a.url" :download="a.name"
            class="p-1.5 rounded hover:bg-dark-700 text-dark-400 hover:text-dark-200 transition-colors"
            title="Baixar arquivo">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
          </a>
          <!-- Deletar -->
          <button @click="deleteFile(a.id)"
            class="p-1.5 rounded hover:bg-red-600/20 text-dark-400 hover:text-red-400 transition-colors"
            title="Excluir arquivo">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-16">
      <div class="w-16 h-16 rounded-2xl bg-dark-800 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-dark-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
        </svg>
      </div>
      <p class="text-dark-300 font-medium">Nenhum arquivo</p>
      <p class="text-dark-500 text-sm mt-1">Faça upload de documentos, imagens e mais</p>
    </div>
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
            :class="isDragging ? 'border-indigo-500 bg-indigo-500/5' : 'border-dark-600 hover:border-dark-500'"
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
              <p class="text-sm text-dark-400">Arraste um arquivo ou <span class="text-indigo-400 font-medium">clique para selecionar</span></p>
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
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { attachmentsApi } from '@/api/projects'
import type { Attachment } from '@/types'

const route = useRoute()
const projectId = Number(route.params.id)
const loading = ref(true)
const uploading = ref(false)
const attachments = ref<Attachment[]>([])

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
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

async function deleteFile(id: number) {
  if (!confirm('Excluir este arquivo?')) return
  await attachmentsApi.delete(projectId, id)
  attachments.value = attachments.value.filter(a => a.id !== id)
}

function fileExt(name: string) {
  return name.split('.').pop()?.toUpperCase().slice(0, 4) ?? 'FILE'
}

function fileIconBg(mime: string) {
  if (mime.includes('pdf')) return 'bg-red-600/20'
  if (mime.includes('image')) return 'bg-blue-600/20'
  if (mime.includes('word') || mime.includes('document')) return 'bg-indigo-600/20'
  if (mime.includes('sheet') || mime.includes('excel')) return 'bg-emerald-600/20'
  return 'bg-dark-700'
}

function fileIconColor(mime: string) {
  if (mime.includes('pdf')) return 'text-red-400'
  if (mime.includes('image')) return 'text-blue-400'
  if (mime.includes('word') || mime.includes('document')) return 'text-indigo-400'
  if (mime.includes('sheet') || mime.includes('excel')) return 'text-emerald-400'
  return 'text-dark-400'
}

function formatSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1048576).toFixed(1)} MB`
}
</script>
