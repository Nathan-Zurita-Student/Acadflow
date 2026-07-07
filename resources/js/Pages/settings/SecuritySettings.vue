<template>
  <div class="space-y-6 max-w-2xl mx-auto">
    <!-- ALTERAR SENHA -->
    <div class="card space-y-4">
      <div>
        <h2 class="text-base font-semibold text-white">Alterar senha</h2>
        <p class="text-xs text-dark-500 mt-0.5">Ao alterar, as demais sessões serão desconectadas.</p>
      </div>
      <form @submit.prevent="changePassword" class="space-y-3" novalidate>
        <div>
          <label class="label">Senha atual</label>
          <input v-model="pwd.current" type="password" class="input" autocomplete="current-password" />
        </div>
        <div>
          <label class="label">Nova senha</label>
          <input v-model="pwd.next" type="password" class="input" autocomplete="new-password" placeholder="Mínimo 8 caracteres" />
          <p class="text-xs text-dark-500 mt-1">Use maiúsculas, minúsculas, números e símbolos.</p>
        </div>
        <div>
          <label class="label">Confirmar nova senha</label>
          <input v-model="pwd.confirmation" type="password" class="input" autocomplete="new-password" />
        </div>
        <p v-if="pwdError" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">{{ pwdError }}</p>
        <button type="submit" :disabled="pwdLoading" class="btn-primary justify-center w-full">
          <span v-if="pwdLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          <span v-else>Alterar senha</span>
        </button>
      </form>
    </div>

    <!-- ALTERAR E-MAIL -->
    <div class="card space-y-4">
      <div>
        <h2 class="text-base font-semibold text-white">Alterar e-mail</h2>
        <p class="text-xs text-dark-500 mt-0.5">E-mail atual: <span class="text-dark-300">{{ auth.user?.email }}</span></p>
      </div>

      <form v-if="emailStep === 'request'" @submit.prevent="requestEmailChange" class="space-y-3" novalidate>
        <div>
          <label class="label">Senha atual</label>
          <input v-model="mail.password" type="password" class="input" autocomplete="current-password" />
        </div>
        <div>
          <label class="label">Novo e-mail</label>
          <input v-model="mail.email" type="email" class="input" placeholder="novo@email.com" autocomplete="email" />
        </div>
        <p v-if="mailError" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">{{ mailError }}</p>
        <button type="submit" :disabled="mailLoading" class="btn-primary justify-center w-full">
          <span v-if="mailLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          <span v-else>Enviar código para o novo e-mail</span>
        </button>
      </form>

      <form v-else @submit.prevent="confirmEmailChange" class="space-y-4" novalidate>
        <p class="text-sm text-dark-300">Enviamos um código para <span class="font-medium text-white">{{ mail.email }}</span>.</p>
        <CodeInput v-model="mail.code" :disabled="mailLoading" @complete="confirmEmailChange" />
        <p v-if="mailError" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2 text-center">{{ mailError }}</p>
        <div class="flex gap-2">
          <button type="button" class="btn-secondary justify-center flex-1" @click="resetEmailFlow">Cancelar</button>
          <button type="submit" :disabled="mailLoading || mail.code.length !== 6" class="btn-primary justify-center flex-1">
            <span v-if="mailLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Confirmar troca</span>
          </button>
        </div>
      </form>
    </div>

    <!-- SESSÕES ATIVAS -->
    <div class="card space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-semibold text-white">Sessões ativas</h2>
          <p class="text-xs text-dark-500 mt-0.5">Dispositivos com acesso à sua conta.</p>
        </div>
        <button
          v-if="sessions.length > 1"
          class="text-xs text-red-400 hover:text-red-300 font-medium"
          @click="revokeOthers"
        >Encerrar as outras</button>
      </div>

      <div v-if="sessionsLoading" class="py-6 text-center text-dark-500 text-sm">Carregando…</div>
      <ul v-else class="space-y-2">
        <li
          v-for="s in sessions" :key="s.id"
          class="flex items-center gap-3 bg-dark-900 border border-dark-700 rounded-lg px-3 py-2.5"
        >
          <Icon :name="platformIcon(s.platform)" :size="20" class="text-dark-400 flex-shrink-0" />
          <div class="min-w-0 flex-1">
            <p class="text-sm text-dark-100 truncate">
              {{ s.browser }} · {{ s.platform }}
              <span v-if="s.is_current" class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-400">este dispositivo</span>
            </p>
            <p class="text-xs text-dark-500">
              {{ s.ip_address ?? 'IP desconhecido' }} · último acesso {{ formatDate(s.last_active) }}
            </p>
          </div>
          <button
            v-if="!s.is_current"
            class="text-xs text-red-400/80 hover:text-red-400 flex-shrink-0"
            @click="revoke(s.id)"
          >Encerrar</button>
        </li>
      </ul>
    </div>

    <!-- MEUS DADOS (LGPD) -->
    <div class="card space-y-4">
      <div>
        <h2 class="text-base font-semibold text-white">Meus dados e privacidade</h2>
        <p class="text-xs text-dark-500 mt-0.5">Baixe uma cópia dos seus dados pessoais em formato legível por máquina (LGPD).</p>
      </div>
      <button :disabled="exporting" class="btn-secondary justify-center w-full" @click="exportData">
        <span v-if="exporting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        <template v-else>
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          Exportar meus dados
        </template>
      </button>
      <p class="text-xs text-dark-500">
        Consulte os
        <RouterLink to="/termos" target="_blank" class="text-accent-400 hover:text-accent-300">Termos de Uso</RouterLink>
        e a
        <RouterLink to="/privacidade" target="_blank" class="text-accent-400 hover:text-accent-300">Política de Privacidade</RouterLink>.
      </p>
    </div>

    <!-- EXCLUIR CONTA -->
    <div class="card space-y-4 border-red-500/30">
      <div>
        <h2 class="text-base font-semibold text-red-400">Excluir conta</h2>
        <p class="text-xs text-dark-500 mt-0.5">
          Cancela sua assinatura (se houver) e remove sua conta. Esta ação é irreversível.
        </p>
      </div>
      <form @submit.prevent="deleteAccount" class="space-y-3" novalidate>
        <div>
          <label class="label">Confirme sua senha</label>
          <input v-model="del.password" type="password" class="input" autocomplete="current-password" />
        </div>
        <p v-if="delError" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">{{ delError }}</p>
        <button type="submit" :disabled="delLoading" class="btn-danger justify-center w-full">
          <span v-if="delLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          <span v-else>Excluir minha conta</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { authApi, type SessionInfo } from '@/api/auth'
import Icon from '@/components/ui/Icon.vue'
import CodeInput from '@/components/ui/CodeInput.vue'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()
const { confirm } = useConfirm()

// ── Alterar senha ──────────────────────────────
const pwd = ref({ current: '', next: '', confirmation: '' })
const pwdError = ref('')
const pwdLoading = ref(false)

async function changePassword() {
  pwdError.value = ''
  if (!pwd.value.current || !pwd.value.next) { pwdError.value = 'Preencha os campos.'; return }
  if (pwd.value.next !== pwd.value.confirmation) { pwdError.value = 'As senhas não coincidem.'; return }
  pwdLoading.value = true
  try {
    await authApi.changePassword({
      current_password: pwd.value.current,
      password: pwd.value.next,
      password_confirmation: pwd.value.confirmation,
    })
    pwd.value = { current: '', next: '', confirmation: '' }
    toast.success('Senha alterada. As outras sessões foram encerradas.')
    loadSessions()
  } catch (e: any) {
    pwdError.value = firstError(e) ?? 'Não foi possível alterar a senha.'
  } finally {
    pwdLoading.value = false
  }
}

// ── Alterar e-mail ─────────────────────────────
const emailStep = ref<'request' | 'confirm'>('request')
const mail = ref({ password: '', email: '', code: '' })
const mailError = ref('')
const mailLoading = ref(false)

async function requestEmailChange() {
  mailError.value = ''
  if (!mail.value.password || !mail.value.email) { mailError.value = 'Preencha os campos.'; return }
  mailLoading.value = true
  try {
    await authApi.requestEmailChange({ current_password: mail.value.password, email: mail.value.email })
    emailStep.value = 'confirm'
    toast.success('Código enviado para o novo e-mail.')
  } catch (e: any) {
    mailError.value = firstError(e) ?? 'Não foi possível iniciar a troca.'
  } finally {
    mailLoading.value = false
  }
}

async function confirmEmailChange() {
  if (mail.value.code.length !== 6 || mailLoading.value) return
  mailError.value = ''
  mailLoading.value = true
  try {
    const { data } = await authApi.confirmEmailChange(mail.value.code)
    auth.setUser(data.user)
    toast.success('E-mail alterado com sucesso.')
    resetEmailFlow()
  } catch (e: any) {
    mailError.value = firstError(e) ?? 'Código inválido.'
    mail.value.code = ''
  } finally {
    mailLoading.value = false
  }
}

function resetEmailFlow() {
  emailStep.value = 'request'
  mail.value = { password: '', email: '', code: '' }
  mailError.value = ''
}

// ── Sessões ────────────────────────────────────
const sessions = ref<SessionInfo[]>([])
const sessionsLoading = ref(false)

async function loadSessions() {
  sessionsLoading.value = true
  try {
    const { data } = await authApi.sessions()
    sessions.value = data.data
  } catch {
    toast.error('Erro ao carregar sessões.')
  } finally {
    sessionsLoading.value = false
  }
}

async function revoke(id: string) {
  try {
    await authApi.revokeSession(id)
    sessions.value = sessions.value.filter((s) => s.id !== id)
    toast.success('Sessão encerrada.')
  } catch {
    toast.error('Erro ao encerrar sessão.')
  }
}

async function revokeOthers() {
  if (!await confirm({ title: 'Encerrar sessões', message: 'Encerrar todas as outras sessões?', variant: 'danger' })) return
  try {
    await authApi.revokeOtherSessions()
    sessions.value = sessions.value.filter((s) => s.is_current)
    toast.success('As demais sessões foram encerradas.')
  } catch {
    toast.error('Erro ao encerrar sessões.')
  }
}

// ── Exportar dados (LGPD) ──────────────────────
const exporting = ref(false)

async function exportData() {
  exporting.value = true
  try {
    const { data } = await authApi.exportData()
    const url = URL.createObjectURL(data as Blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `acadflow-meus-dados-${new Date().toISOString().slice(0, 10)}.json`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
    toast.success('Seus dados foram exportados.')
  } catch {
    toast.error('Não foi possível exportar os dados agora.')
  } finally {
    exporting.value = false
  }
}

// ── Excluir conta ──────────────────────────────
const del = ref({ password: '' })
const delError = ref('')
const delLoading = ref(false)

async function deleteAccount() {
  delError.value = ''
  if (!del.value.password) { delError.value = 'Informe sua senha.'; return }
  if (!await confirm({
    title: 'Excluir conta',
    message: 'Tem certeza? Sua assinatura será cancelada e sua conta removida. Esta ação é irreversível.',
    variant: 'danger',
    confirmText: 'Excluir conta',
  })) return

  delLoading.value = true
  try {
    await authApi.deleteAccount(del.value.password)
    auth.setUser(null)
    toast.success('Conta excluída.')
    router.replace('/login')
  } catch (e: any) {
    delError.value = firstError(e) ?? 'Não foi possível excluir a conta.'
  } finally {
    delLoading.value = false
  }
}

// ── Helpers ────────────────────────────────────
function firstError(e: any): string | undefined {
  const errors = e.response?.data?.errors as Record<string, string[]> | undefined
  return errors ? Object.values(errors)[0]?.[0] : e.response?.data?.message
}

function formatDate(value: string | null): string {
  if (!value) return '—'
  return new Date(value).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
}

function platformIcon(platform: string): string {
  const p = platform.toLowerCase()
  if (p.includes('android') || p.includes('ios') || p.includes('iphone')) return 'smartphone'
  return 'computer'
}

onMounted(loadSessions)
</script>
