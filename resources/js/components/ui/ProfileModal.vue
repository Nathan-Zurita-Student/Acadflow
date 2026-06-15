<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in"
      @click.self="$emit('close')">
      <div class="w-full max-w-md bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-dark-700">
          <h2 class="font-semibold text-white">Editar Perfil</h2>
          <button @click="$emit('close')" class="p-1 rounded-lg hover:bg-dark-700 text-dark-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-4">

          <!-- Avatar -->
          <div class="flex items-center gap-4">
            <div class="relative flex-shrink-0">
              <div class="w-16 h-16 rounded-full overflow-hidden bg-accent-600/30 flex items-center justify-center">
                <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" alt="Avatar" />
                <span v-else class="text-2xl font-bold text-accent-400">{{ displayInitial }}</span>
              </div>
              <label
                class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-accent-600 flex items-center justify-center cursor-pointer hover:bg-accent-500 transition-colors">
                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="onAvatarSelect" />
              </label>
            </div>
            <div>
              <p class="text-sm font-medium text-dark-200">Foto de perfil</p>
              <p class="text-xs text-dark-500">JPG, PNG ou GIF — máx. 20MB (suporta 4K)</p>
            </div>
          </div>

          <!-- Nome -->
          <div>
            <label class="label">Nome completo</label>
            <input v-model="form.name" class="input" placeholder="Seu nome" />
          </div>

          <!-- Nome exibido -->
          <div>
            <label class="label">Nome exibido <span class="text-dark-600 font-normal">(apelido, nome curto)</span></label>
            <input v-model="form.display_name" class="input" placeholder="Como você quer ser chamado..." />
          </div>

          <!-- Login (email) -->
          <div>
            <label class="label">E-mail (login)</label>
            <input v-model="form.email" type="email" class="input" placeholder="seu@email.com" />
          </div>

          <!-- Nova senha -->
          <div>
            <label class="label">Nova senha <span class="text-dark-600 font-normal">(deixe em branco para manter)</span></label>
            <input v-model="form.password" type="password" class="input" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
          </div>

          <!-- Confirmar senha -->
          <div v-if="form.password">
            <label class="label">Confirmar nova senha</label>
            <input v-model="form.password_confirmation" type="password" class="input" placeholder="Repita a senha" autocomplete="new-password" />
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">{{ error }}</p>
          <p v-if="success" class="text-sm text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 rounded-lg px-3 py-2">Perfil atualizado!</p>

          <div class="flex gap-3 pt-1">
            <button @click="$emit('close')" class="btn-secondary flex-1 justify-center">Cancelar</button>
            <button @click="save" :disabled="saving" class="btn-primary flex-1 justify-center">
              <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              <span v-else>Salvar</span>
            </button>
          </div>

        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const emit = defineEmits(['close'])

const auth = useAuthStore()
const saving = ref(false)
const error = ref('')
const success = ref(false)
const avatarInput = ref<HTMLInputElement | null>(null)
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(auth.user?.avatar ?? null)

const form = ref({
  name: auth.user?.name ?? '',
  display_name: auth.user?.display_name ?? '',
  email: auth.user?.email ?? '',
  password: '',
  password_confirmation: '',
})

const displayInitial = computed(() => {
  const label = form.value.display_name || form.value.name
  return label?.[0]?.toUpperCase() ?? '?'
})

function onAvatarSelect(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function save() {
  error.value = ''
  success.value = false

  if (form.value.password && form.value.password !== form.value.password_confirmation) {
    error.value = 'As senhas não coincidem.'
    return
  }

  saving.value = true
  try {
    const fd = new FormData()
    fd.append('name', form.value.name)
    fd.append('display_name', form.value.display_name)
    fd.append('email', form.value.email)
    if (form.value.password) {
      fd.append('password', form.value.password)
      fd.append('password_confirmation', form.value.password_confirmation)
    }
    if (avatarFile.value) fd.append('avatar', avatarFile.value)

    await auth.updateProfile(fd)
    success.value = true
    form.value.password = ''
    form.value.password_confirmation = ''
    setTimeout(() => emit('close'), 1200)
  } catch (e: any) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Erro ao salvar perfil.'
  } finally {
    saving.value = false
  }
}
</script>
