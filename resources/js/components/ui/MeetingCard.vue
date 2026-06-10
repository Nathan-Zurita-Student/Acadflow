<template>
  <div class="card group hover:border-dark-600 transition-colors">
    <div class="flex items-start gap-4">
      <!-- Date badge -->
      <div class="flex-shrink-0 w-14 text-center">
        <div class="bg-indigo-600/20 border border-indigo-500/30 rounded-xl px-2 py-2">
          <p class="text-[10px] font-semibold text-indigo-400 uppercase">{{ monthAbbr }}</p>
          <p class="text-2xl font-bold text-indigo-300 leading-none">{{ day }}</p>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <h3 class="font-semibold text-dark-100 truncate">{{ meeting.title }}</h3>
            <div class="flex items-center gap-3 mt-1 text-xs text-dark-500">
              <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ time }}
              </span>
              <span v-if="meeting.location" class="flex items-center gap-1 truncate">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ meeting.location }}
              </span>
            </div>
          </div>

          <!-- Actions (visible on hover) -->
          <div v-if="canEdit" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
            <button @click="$emit('edit')"
              class="p-1.5 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-dark-200 transition-colors">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button @click="$emit('delete')"
              class="p-1.5 rounded-lg hover:bg-red-600/20 text-dark-400 hover:text-red-400 transition-colors">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>

        <p v-if="meeting.description" class="text-sm text-dark-400 mt-2 line-clamp-2 leading-relaxed">
          {{ meeting.description }}
        </p>

        <div v-if="meeting.notes" class="mt-3 px-3 py-2 bg-dark-900/60 rounded-lg border-l-2 border-indigo-500/40">
          <p class="text-xs font-semibold text-dark-500 mb-1">Ata</p>
          <p class="text-xs text-dark-400 leading-relaxed whitespace-pre-wrap line-clamp-3">{{ meeting.notes }}</p>
        </div>

        <div class="flex items-center gap-2 mt-3">
          <div class="w-5 h-5 rounded-full bg-indigo-600/30 flex items-center justify-center text-[10px] font-bold text-indigo-300">
            {{ meeting.creator?.name[0] }}
          </div>
          <span class="text-xs text-dark-600">{{ meeting.creator?.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Meeting } from '@/types'

const props = defineProps<{
  meeting: Meeting
  canEdit?: boolean
}>()

defineEmits<{ edit: []; delete: [] }>()

const date = computed(() => new Date(props.meeting.scheduled_at))

const monthAbbr = computed(() =>
  date.value.toLocaleString('pt-BR', { month: 'short' }).replace('.', '')
)
const day  = computed(() => date.value.getDate().toString().padStart(2, '0'))
const time = computed(() =>
  date.value.toLocaleString('pt-BR', { hour: '2-digit', minute: '2-digit' })
)
</script>
