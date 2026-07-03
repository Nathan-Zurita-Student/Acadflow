<template>
  <div class="space-y-6 animate-fade-in max-w-4xl mx-auto">
    <div>
      <h1 class="text-xl font-bold text-white">Configurações</h1>
      <p class="text-dark-400 text-sm mt-0.5">Gerencie seu perfil, plano e o Kanban dos seus projetos.</p>
    </div>

    <!-- Abas -->
    <div class="flex gap-1 border-b border-dark-700">
      <RouterLink
        v-for="tab in tabs" :key="tab.key"
        :to="`/settings/${tab.key}`"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="activeTab === tab.key
          ? 'border-accent-500 text-accent-400'
          : 'border-transparent text-dark-400 hover:text-dark-200'"
      >{{ tab.label }}</RouterLink>
    </div>

    <!-- PERFIL -->
    <div v-if="activeTab === 'profile'" class="card max-w-md mx-auto space-y-4">
      <div class="flex items-center gap-4">
        <div class="relative flex-shrink-0">
          <div class="w-16 h-16 rounded-full overflow-hidden bg-accent-600/30 flex items-center justify-center">
            <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" alt="Avatar" />
            <span v-else class="text-2xl font-bold text-accent-400">{{ displayInitial }}</span>
          </div>
          <label class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-accent-600 flex items-center justify-center cursor-pointer hover:bg-accent-500 transition-colors">
            <Icon name="photo_camera" :size="14" class="text-white" />
            <input type="file" accept="image/*" class="hidden" @change="onAvatarSelect" />
          </label>
        </div>
        <div>
          <p class="text-sm font-medium text-dark-200">Foto de perfil</p>
          <p class="text-xs text-dark-500">JPG, PNG ou GIF — máx. 20MB</p>
        </div>
      </div>

      <div>
        <label class="label">Nome completo</label>
        <input v-model="profile.name" class="input" placeholder="Seu nome" />
      </div>
      <div>
        <label class="label">Nome exibido <span class="text-dark-600 font-normal">(apelido)</span></label>
        <input v-model="profile.display_name" class="input" placeholder="Como quer ser chamado" />
      </div>
      <div>
        <label class="label">E-mail (login)</label>
        <input :value="auth.user?.email" type="email" class="input opacity-70 cursor-not-allowed" disabled />
        <p class="text-xs text-dark-500 mt-1">
          Para trocar o e-mail ou a senha, use a aba
          <RouterLink to="/settings/security" class="text-accent-400 hover:text-accent-300">Segurança</RouterLink>.
        </p>
      </div>

      <button @click="saveProfile" :disabled="savingProfile" class="btn-primary justify-center w-full">
        <span v-if="savingProfile" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        <span v-else>Salvar perfil</span>
      </button>
    </div>

    <!-- SEGURANÇA -->
    <SecuritySettings v-else-if="activeTab === 'security'" />

    <!-- PLANOS (reaproveita a página de planos) -->
    <PlansPage v-else-if="activeTab === 'plans'" :embedded="true" />

    <!-- KANBAN -->
    <div v-else-if="activeTab === 'kanban'" class="relative">
      <!-- Editor (desfocado e travado para o plano gratuito) -->
      <div :class="isPro ? '' : 'blur-[3px] pointer-events-none select-none'" aria-hidden="false">
        <div class="card space-y-4">
          <div>
            <label class="label">Projeto</label>
            <select v-model="selectedProjectId" class="input max-w-sm" @change="loadColumns">
              <option :value="null" disabled>Selecione um projeto…</option>
              <option v-for="p in editableProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <p v-if="isPro && !editableProjects.length" class="text-xs text-dark-500 mt-2">
              Você precisa ser líder/dono de um projeto para configurar o Kanban.
            </p>
          </div>

          <!-- Lista de colunas -->
          <div v-if="columns.length" class="space-y-2">
            <p class="label">Colunas do Kanban</p>
            <div
              v-for="(col, i) in columns" :key="col.id"
              class="flex items-center gap-2 bg-dark-900 border border-dark-700 rounded-lg px-3 py-2"
            >
              <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="dotFor(col.color)" />
              <input
                v-model="col.label"
                class="flex-1 bg-transparent text-sm text-dark-100 focus:outline-none"
                @blur="renameColumn(col)"
                @keyup.enter="renameColumn(col)"
              />
              <select v-model="col.color" class="text-xs bg-dark-800 border border-dark-700 rounded px-1.5 py-1 text-dark-300" @change="renameColumn(col)">
                <option v-for="c in palette" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
              <button class="p-1 text-dark-500 hover:text-dark-200 disabled:opacity-30" :disabled="i === 0" @click="move(i, -1)">
                <Icon name="keyboard_arrow_up" :size="16" />
              </button>
              <button class="p-1 text-dark-500 hover:text-dark-200 disabled:opacity-30" :disabled="i === columns.length - 1" @click="move(i, 1)">
                <Icon name="keyboard_arrow_down" :size="16" />
              </button>
              <button
                v-if="!col.is_default"
                class="p-1 text-red-400/70 hover:text-red-400"
                @click="removeColumn(col)"
              ><Icon name="delete" :size="16" /></button>
              <span v-else class="text-[10px] text-dark-600 px-1">padrão</span>
            </div>

            <!-- Adicionar coluna -->
            <div class="pt-2 flex gap-2">
              <input
                v-model="newColumnLabel"
                class="input flex-1"
                placeholder="Nome da nova coluna…"
                @keyup.enter="addColumn"
              />
              <button class="btn-primary text-sm" :disabled="!newColumnLabel.trim() || addingColumn" @click="addColumn">
                Adicionar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Overlay de bloqueio (somente plano gratuito) -->
      <div v-if="!isPro" class="absolute inset-0 flex items-start justify-center pt-10 px-4">
        <div class="card max-w-md w-full text-center space-y-3 border-accent-500/40 shadow-2xl">
          <div class="w-12 h-12 mx-auto rounded-full bg-accent-600/20 flex items-center justify-center">
            <Icon name="lock" :size="26" class="text-accent-400" />
          </div>
          <h3 class="text-lg font-bold text-white">Personalize seu Kanban</h3>
          <p class="text-sm text-dark-400">
            A personalização do quadro é exclusiva dos planos
            <strong class="text-accent-300">Pro</strong> e <strong class="text-accent-300">Ultra Pro</strong>.
          </p>

          <ul class="text-sm text-dark-200 space-y-1.5 text-left max-w-xs mx-auto">
            <li class="flex items-center gap-2"><Icon name="edit" :size="16" class="text-accent-400" /> Renomear as colunas com a linguagem do seu grupo</li>
            <li class="flex items-center gap-2"><Icon name="add" :size="16" class="text-accent-400" /> Criar quantas colunas o seu fluxo precisar</li>
            <li class="flex items-center gap-2"><Icon name="palette" :size="16" class="text-accent-400" /> Definir a cor de cada etapa</li>
            <li class="flex items-center gap-2"><Icon name="swap_vert" :size="16" class="text-accent-400" /> Reordenar o fluxo do "A fazer" ao "Entregue"</li>
          </ul>

          <p class="text-xs text-dark-500">
            Deixe o quadro com a cara do processo do seu grupo e organize as entregas do jeito de vocês.
          </p>

          <RouterLink to="/settings/plans" class="btn-primary justify-center w-full">
            <Icon name="rocket_launch" :size="16" /> Fazer upgrade
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { projectsApi, columnsApi } from '@/api/projects'
import Icon from '@/components/ui/Icon.vue'
import PlansPage from '@/Pages/billing/PlansPage.vue'
import SecuritySettings from '@/Pages/settings/SecuritySettings.vue'
import type { Project, ProjectColumn } from '@/types'

const route = useRoute()
const auth = useAuthStore()
const toast = useToast()

const tabs = [
  { key: 'profile',  label: 'Perfil' },
  { key: 'security', label: 'Segurança' },
  { key: 'plans',    label: 'Planos' },
  { key: 'kanban',   label: 'Kanban' },
]
const activeTab = computed(() => {
  const t = route.params.tab as string
  return tabs.some(tab => tab.key === t) ? t : 'profile'
})

const isPro = computed(() => auth.user?.plan === 'pro' || auth.user?.plan === 'ultra')

// ── Perfil ─────────────────────────────────────────────
const savingProfile = ref(false)
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(auth.user?.avatar ?? null)
const profile = ref({
  name: auth.user?.name ?? '',
  display_name: auth.user?.display_name ?? '',
})
const displayInitial = computed(() =>
  (profile.value.display_name || profile.value.name)?.[0]?.toUpperCase() ?? '?'
)

function onAvatarSelect(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function saveProfile() {
  savingProfile.value = true
  try {
    const fd = new FormData()
    fd.append('name', profile.value.name)
    fd.append('display_name', profile.value.display_name)
    if (avatarFile.value) fd.append('avatar', avatarFile.value)
    await auth.updateProfile(fd)
    toast.success('Perfil atualizado!')
  } catch (e: any) {
    toast.error(e.response?.data?.message ?? 'Erro ao salvar perfil.')
  } finally {
    savingProfile.value = false
  }
}

// ── Kanban ─────────────────────────────────────────────
const projects = ref<Project[]>([])
const selectedProjectId = ref<number | null>(null)
const columns = ref<ProjectColumn[]>([])
const newColumnLabel = ref('')
const addingColumn = ref(false)

const editableProjects = computed(() =>
  projects.value.filter(p => p.is_owner || p.members?.some(m => m.id === auth.user?.id && m.role === 'leader'))
)

const palette = [
  { value: 'text-slate-400',   label: 'Cinza' },
  { value: 'text-yellow-400',  label: 'Amarelo' },
  { value: 'text-blue-400',    label: 'Azul' },
  { value: 'text-purple-400',  label: 'Roxo' },
  { value: 'text-emerald-400', label: 'Verde' },
  { value: 'text-red-400',     label: 'Vermelho' },
  { value: 'text-pink-400',    label: 'Rosa' },
  { value: 'text-orange-400',  label: 'Laranja' },
]

function dotFor(color: string) {
  return color?.replace('text-', 'bg-').replace(/-\d+$/, '-500') ?? 'bg-slate-500'
}

async function loadProjects() {
  try {
    const { data } = await projectsApi.list()
    projects.value = data
    if (!selectedProjectId.value && editableProjects.value.length) {
      selectedProjectId.value = editableProjects.value[0].id
      await loadColumns()
    }
  } catch { toast.error('Erro ao carregar projetos.') }
}

async function loadColumns() {
  if (!selectedProjectId.value) return
  try {
    const { data } = await columnsApi.list(selectedProjectId.value)
    columns.value = data
  } catch { toast.error('Erro ao carregar colunas.') }
}

async function renameColumn(col: ProjectColumn) {
  if (!selectedProjectId.value || !col.label.trim()) return
  try {
    await columnsApi.update(selectedProjectId.value, col.id, { label: col.label, color: col.color })
  } catch { toast.error('Erro ao salvar coluna.') }
}

async function addColumn() {
  if (!selectedProjectId.value || !newColumnLabel.value.trim()) return
  addingColumn.value = true
  try {
    const { data } = await columnsApi.create(selectedProjectId.value, { label: newColumnLabel.value.trim() })
    columns.value.push(data)
    newColumnLabel.value = ''
    toast.success('Coluna adicionada.')
  } catch (e: any) {
    toast.error(e.response?.data?.message ?? 'Erro ao adicionar coluna.')
  } finally {
    addingColumn.value = false
  }
}

async function removeColumn(col: ProjectColumn) {
  if (!selectedProjectId.value) return
  const { confirm } = useConfirm()
  if (!await confirm({ title: 'Excluir coluna', message: `Excluir a coluna "${col.label}"? As tarefas dela voltam para a primeira coluna.`, variant: 'danger' })) return
  try {
    await columnsApi.delete(selectedProjectId.value, col.id)
    columns.value = columns.value.filter(c => c.id !== col.id)
    toast.success('Coluna excluída.')
  } catch (e: any) {
    toast.error(e.response?.data?.message ?? 'Erro ao excluir coluna.')
  }
}

async function move(index: number, dir: -1 | 1) {
  const target = index + dir
  if (target < 0 || target >= columns.value.length || !selectedProjectId.value) return
  const arr = columns.value.slice()
  ;[arr[index], arr[target]] = [arr[target], arr[index]]
  columns.value = arr
  try {
    await columnsApi.reorder(selectedProjectId.value, arr.map(c => c.id))
  } catch { toast.error('Erro ao reordenar.') }
}

watch(activeTab, (tab) => {
  if (tab === 'kanban' && !projects.value.length) loadProjects()
})

onMounted(() => {
  if (activeTab.value === 'kanban') loadProjects()
})
</script>
