<template>
  <div class="space-y-6 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Arquivos</h1>
        <p class="text-dark-400 text-sm">{{ attachments.length }} arquivo{{ attachments.length !== 1 ? 's' : '' }}</p>
      </div>
      <label class="btn-primary cursor-pointer">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        Upload
        <input type="file" class="hidden" @change="handleUpload" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.zip,.txt" />
      </label>
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
        class="card hover:border-dark-600 transition-colors flex items-center gap-4">
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
        <div class="flex items-center gap-1 flex-shrink-0">
          <a :href="a.url" target="_blank" class="p-1.5 rounded hover:bg-dark-700 text-dark-400 hover:text-dark-200 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
          </a>
          <button @click="deleteFile(a.id)" class="p-1.5 rounded hover:bg-red-600/20 text-dark-400 hover:text-red-400 transition-colors">
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

onMounted(async () => {
  try {
    const res = await attachmentsApi.list(projectId)
    attachments.value = res.data
  } finally { loading.value = false }
})

async function handleUpload(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  uploading.value = true
  const input = e.target as HTMLInputElement
  try {
    const res = await attachmentsApi.upload(projectId, file)
    attachments.value.unshift(res.data)
  } finally {
    uploading.value = false
    input.value = ''
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
