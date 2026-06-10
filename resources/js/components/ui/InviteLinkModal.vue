<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
      @click.self="$emit('close')"
    >
      <div class="bg-dark-800 rounded-2xl border border-dark-700 w-full max-w-md shadow-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-dark-700">
          <h2 class="text-lg font-semibold text-white">Gerar link de convite</h2>
          <button @click="$emit('close')" class="p-1.5 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-5">
          <!-- Role selector (leader only) -->
          <div v-if="canSetLeader">
            <label class="block text-sm font-medium text-dark-300 mb-2">Função do convidado</label>
            <div class="flex gap-3">
              <button
                v-for="opt in roleOptions" :key="opt.value"
                @click="selectedRole = opt.value"
                class="flex-1 py-2.5 px-4 rounded-xl text-sm font-medium border transition-all"
                :class="selectedRole === opt.value
                  ? 'bg-accent-600/20 border-accent-500 text-accent-300'
                  : 'bg-dark-900 border-dark-700 text-dark-400 hover:border-dark-500'"
              >{{ opt.label }}</button>
            </div>
          </div>
          <div v-else class="flex items-center gap-2 text-sm text-dark-400 bg-dark-900 rounded-xl p-3">
            <svg class="w-4 h-4 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            Apenas o líder pode gerar links de líder. Seu link será de <strong class="text-dark-200 ml-1">Membro</strong>.
          </div>

          <!-- Generate button -->
          <button
            @click="generate"
            :disabled="loading"
            class="w-full py-2.5 bg-accent-600 hover:bg-accent-700 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition-colors"
          >
            {{ loading ? 'Gerando...' : 'Gerar link' }}
          </button>

          <!-- Generated link -->
          <div v-if="generatedLink" class="space-y-3">
            <div class="flex items-center gap-2 bg-dark-900 border border-dark-700 rounded-xl p-3">
              <input
                :value="generatedLink"
                readonly
                class="flex-1 bg-transparent text-sm text-dark-300 outline-none truncate"
              />
              <button
                @click="copyLink"
                class="flex-shrink-0 px-3 py-1 text-xs font-semibold rounded-lg transition-colors"
                :class="copied ? 'bg-emerald-600/20 text-emerald-400' : 'bg-accent-600/20 text-accent-400 hover:bg-accent-600/30'"
              >
                {{ copied ? 'Copiado!' : 'Copiar' }}
              </button>
            </div>
            <p class="text-xs text-dark-500 text-center">
              Link válido por 7 dias · Função: <span class="text-dark-300 font-medium">{{ roleLabel }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { inviteApi } from '@/api/projects'

const props = defineProps<{
  projectId: number
  canSetLeader: boolean
}>()

defineEmits<{ close: [] }>()

const selectedRole = ref<'member' | 'leader'>('member')
const loading = ref(false)
const generatedLink = ref('')
const copied = ref(false)

const roleOptions = [
  { value: 'member' as const, label: 'Membro' },
  { value: 'leader' as const, label: 'Líder' },
]

const roleLabel = computed(() => selectedRole.value === 'leader' ? 'Líder' : 'Membro')

async function generate() {
  loading.value = true
  generatedLink.value = ''
  try {
    const role = props.canSetLeader ? selectedRole.value : 'member'
    const { data } = await inviteApi.generate(props.projectId, role)
    generatedLink.value = `${window.location.origin}/invite/${data.token}`
  } finally {
    loading.value = false
  }
}

async function copyLink() {
  await navigator.clipboard.writeText(generatedLink.value)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}
</script>
